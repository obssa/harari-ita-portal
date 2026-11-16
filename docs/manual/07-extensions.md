# Extensions: apps and sharing

For Super Admins and the staff who share news. Each section says who may do it and what the Agency must
provide first. Amharic version: [07-extensions.am.md](07-extensions.am.md).

## 1. API keys for apps

Who: Super Admins (**Administration → API keys**).

The portal answers apps such as the Agency's mobile app or an office kiosk at `/api/v1`, with what the
public site publishes: news and pages, events, documents, offices and leaders, projects and search, in every
language. Developers find the full description at `/api/v1/openapi.json` (also behind **API description** on
the API keys page).

1. **Issue a key**: a name for the app or device, what it may read, and how many requests a minute it may
   make (60 is plenty for an app). An expiry date is optional.
2. The key is shown **once**. Copy it and give it to the app's developer by a safe channel; the portal keeps
   only a fingerprint of it.
3. The list shows the first characters of each key, its status and when it was last used.
4. **Revoke** a key that leaked or is no longer used. Apps using it stop at once; issue a new key instead.

## 2. Sharing a post to Facebook and Telegram

Who: Publishers, Communications Officers and Admins.

1. Open a published post and choose **Share**.
2. Tick the Facebook pages and Telegram channels (the social sources under **Social media**), choose the
   language, and write your own words if you like; otherwise the title and summary are posted. The link to
   the post is always added.
3. The post's **Shared** tab shows each page or channel, whether it worked, a link to the shared post, who
   shared it and when.

Facebook needs a page token allowed to publish (kept with the social source or under Integrations); for
Telegram, the portal's bot must be an administrator of the channel. Until then the tab shows the reason a
share failed.

## Screens

Screenshots follow once the Agency's staging site is set up (phase 1).
