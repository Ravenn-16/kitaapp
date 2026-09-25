# OTP delivery on Render Free

Render Free blocks outbound SMTP ports 25, 465 and 587, including Gmail SMTP.
KITA now includes the official Resend PHP SDK for Laravel's existing `resend`
mail driver, which sends through HTTPS. Password and OTP verification remain intact.

## Activate delivery

1. Create a Resend account at https://resend.com and create a sending API key.
2. Add a domain you own in Resend and complete its DNS verification. You cannot
   verify `gmail.com` or Render's `onrender.com` domain as your own.
3. In Render > KITA service > Environment, set:

   ```env
   MAIL_MAILER=resend
   RESEND_API_KEY=YOUR_PRIVATE_RESEND_API_KEY
   MAIL_FROM_ADDRESS=otp@YOUR_VERIFIED_DOMAIN
   MAIL_FROM_NAME=KITA
   ```

4. Save and deploy the latest commit. Container startup rebuilds Laravel's config
   cache; no paid Render Shell is necessary. SMTP variables are unused with this driver.
5. Log in using a real account email you control. Check your inbox/spam and Resend's
   email delivery logs. Never paste the API key or OTP into public logs or GitHub.

## Initial test without a domain

Resend's `onboarding@resend.dev` sender can send only to the exact email address
associated with your Resend account. Set `MAIL_FROM_ADDRESS=onboarding@resend.dev`
and test a KITA account with that same recipient address. Do not assume Gmail
plus-address aliases are allowed by this restriction. Sending to all four demo
addresses or other users requires a verified domain and sender.

## Troubleshooting

- No API request in Resend: check that the deployed commit includes the SDK,
  `MAIL_MAILER=resend`, and a nonempty key, then redeploy.
- Unauthorized response: replace the invalid/revoked API key in Render.
- Forbidden response: check the verified sender domain, key permissions and
  testing-recipient restriction.
- Rate/quota errors: check your provider limits.
- Accepted but no email: inspect delivery status and recipient spam filters.
- Keep `APP_ENV=production` and `APP_DEBUG=false`; do not use `log`/`array` mail
  drivers or bypass OTP to make login appear successful.

No live delivery can be verified until the provider account, API key and sender
are configured. Changing your local `.env` does not update Render's environment.

References:
- https://render.com/docs/free#other-limitations
- https://resend.com/php
- https://resend.com/docs/knowledge-base/403-error-resend-dev-domain
