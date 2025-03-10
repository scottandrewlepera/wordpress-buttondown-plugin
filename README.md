# Wordpress + Buttondown Plugin

Connect your [Buttondown](https://buttondown.com) newsletter to your Wordpress website and encourage subscriptions with subscribers-only content.

## How it works

This plugin uses your Buttondown subscriber list as a "soft paywall" for special content on your Wordpress site. Once the plugin is set up, you can author Wordpress content that can only be seen by your Buttondown subscribers. You can also create separate content for paid vs. free subscribers, encouraging paid subscriptions.

Visitors will be asked to login with the email they used to subscribe to your Buttondown newsletter. The plugin then uses the [Buttondown API](https://docs.buttondown.com/api-introduction) to confirm if an email address is subscribed to your newsletter and if they are free or paid subscribers.

## Important caveats

- This is a lightweight integration. There are no passwords involved, just a lookup to confirm an email is subscribed to your Buttondown newsletter. If you want a more robust solution, consider [JSON Web Tokens](https://jwt.io/).
- Usage may subject your website to GDPR data protections, maybe? It's up to you to figure this out.

## Requirements

You'll need:

- A Wordpress website with permissions to upload plugins
- a [Buttondown](https://buttondown.com) account with an API token

## Installation

1. Download the latest ZIP file
2. From your WP admin dashboard, choose **Plugins > Add New Plugin**
3. Click the **Upload Plugin** button
4. Click the **Browse** button to open a file picker window
5. Select the ZIP file and upload it.
6. Click **Install Now**
7. Once installed, click **Activate Plugin**

## Settings

Before you can use the plugin, you need to configure it. Go to **Settings > WP Buttondown** to see your configuration options.

### Buttondown settings

* **Buttondown API Token** - The API token from your Buttondown account. You will need to activate API usage on your account.
* **Buttondown subscription page (optional)** - The URL of your Buttondown newsletter subscription page, either at buttondown.com or somewhere on your Wordpress site.
* **Generate new cookies (optional)** - Check this box to generate new cookies, forcing all vistors to login again on their next visit.

### Login and landing page configuration

These are the pages that visitors will be redirected to for the login process. You must either create these pages yourself or the plugin can create them for you. The plugin provides default values you can use.

To have the plugin create the landing pages, check the **Create pages on update** checkbox before saving your settings.

NOTE: Landing pages are always top-level pages and cannot have a parent.

### Adding the login form to any page

Use the `wp_buttondown_login_form` shortcode to add the login form to any post or page that allows shortcodes.

```
[wp_buttondown_login_form]
```

This will create the following form:

<img src="./login-form-sample.png" style="max-width: 500px;" />

### Testing the plugin

Test your setup by submitting a valid email address that is subscribed to your Buttondown newsletter. If all is working correctly, the browser will be redirected to the "success" page specified in the plugin settings.

## Creating subscriber-only content

To create content that is only visible to subscribers, use the `wp_buttondown_regular` and `wp_buttondown_premium` shortcodes to enclose that content.

NOTE: Premium subscribers also have access to regular content by default, so you should not need to duplicate content for both free and premium subscribers.

```
<!-- This content will only be shown to both free and paid subscribers -->

[wp_buttondown_regular]

<h2>Secret Plans</h2>
<a href="/secret-plans.pdf">Download a PDF of my Secret Plans!</a>

[/wp_buttondown_regular]

<!-- This content will only be shown to paid subscribers -->

<h2>My Grandmother's Cranberry Dandelion Tea</h2>

[wp<_buttondown_premium]

- 1/2 cup unsweetened cranderry juice
- 1-2 tea bags roasted dandelion root tea
- Lemon juice

Steep tea bags in boiling water 5-10 minutes. Allow to cool. In a pitcher, combine cranberry juice with four (4) cups cold water. Add tea and stir. Add lemon juice to taste. Serve chilled or with ice.

[/wp_buttondown_premium]

```

## Troubleshooting

### Blank page or JSON error instead of redirect after login
- Check that your landing pages actually exist and that there are no typos.
- Make sure your Buttondown token exists and is correct.

### The login form shows "temporarily offline" message
This usually means your API token can't be retrieved. This happens if you migrate your site to another host or domain. To fix this, visit the settings page and re-enter your API token.
