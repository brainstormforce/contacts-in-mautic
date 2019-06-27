=== Contacts in Mautic ===
Contributors: brainstormforce
Tags: mautic, contacts, api, count, email
Requires at least: 4.1
Stable tag: 1.0.2
Tested up to: 5.2

Display your Mautic Contacts count on your website

== Description ==
A very useful plugin to display your mautic contacts count on your website. This plugin generate the shortcode [mauticcount]. Use this shortocde in your website & share your happy :) customer count with visitors.  If you want to include anonymous contacts in count use shortcode `[mauticcount anonymous="on"]`.

# Configurations

- Go to WordPress Dashboard -> Settings -> Mautic Contacts Count Page
- Enter Mautic Base URL
- Enter Public Key and Secret Key

# How To Use Shortcode

* To display Mautic contacts count use following shorcode

`[mauticcount]`

* To display all contacts including anonymous contacts

`[mauticcount anonymous="on"]`

== Changelog ==

# Version 1.0.2 

- Fix: Prevent fatal error when plugin incorrect response is received.

# Version 1.0.1
- Improvement: Change the Cron to update contacts count to be executed every week rather than every day, making fewer requests to Mautic
- Fix: Add correct comma separate formatting for the contacts count.

# Version 1.0.0
- Initial Release.

== Installation ==
# How To Get Mautic API Credentials 

Need help to get Mautic API credentials? Refer [this doc](https://docs.brainstormforce.com/how-to-get-mautic-api-credentials/) to know How to get mautic credentials.
