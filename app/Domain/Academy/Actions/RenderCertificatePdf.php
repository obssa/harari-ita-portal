<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Models\Certificate;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

/**
 * A certificate as an A4 landscape PDF (ADR-0030 addendum), in the learner's language, with the same design as the
 * printable sheet. dompdf runs in PHP on any host; it reads only the bundled fonts and the logo, never the network.
 */
final class RenderCertificatePdf
{
    /** Runs of Ethiopic, Ethiopic Supplement, Extended and Extended-A, with the spaces between their words. */
    private const ETHIOPIC = '/[\x{1200}-\x{139F}\x{2D80}-\x{2DDF}\x{AB00}-\x{AB2F}\x{1E7E0}-\x{1E7FF}]+(?:\s+[\x{1200}-\x{139F}\x{2D80}-\x{2DDF}\x{AB00}-\x{AB2F}\x{1E7E0}-\x{1E7FF}]+)*/u';

    public function handle(Certificate $certificate, bool $compress = true): string
    {
        $certificate->loadMissing(['course.translations', 'cohort', 'enrolment']);
        $cache = storage_path('fonts');
        File::ensureDirectoryExists($cache);

        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setIsFontSubsettingEnabled(true);
        $options->setChroot([resource_path('fonts/pdf'), public_path('brand')]);
        $options->setFontDir($cache);
        $options->setFontCache($cache);
        $options->setDefaultFont('inter');
        $options->setDefaultPaperSize('a4');
        $options->setDefaultPaperOrientation('landscape');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('pdf.certificate', [
            'certificate' => $certificate,
            'fonts' => resource_path('fonts/pdf'),
        ])->render(), 'UTF-8');
        $dompdf->render();

        return (string) $dompdf->output(['compress' => $compress ? 1 : 0]);
    }

    /**
     * Escaped text with each run of Ethiopic script in its own font: dompdf does not fall back from one font to
     * another per letter, and a name or a title can mix scripts in any language.
     */
    public static function text(?string $text): HtmlString
    {
        return new HtmlString((string) preg_replace_callback(
            self::ETHIOPIC,
            fn (array $run): string => '<span class="eth">'.$run[0].'</span>',
            e((string) $text),
        ));
    }

    public static function filename(Certificate $certificate): string
    {
        return 'certificate-'.$certificate->code.'.pdf';
    }
}
