=== Contacts in Mautic ===
Contributors: pratikchaskar
Tags: mautic, contacts, api, count, email
Requires at least: 4.1
Stable tag: 1.0.7
Tested up to: 6.1

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

=  Version 1.0.7  = 
- Improvement: Added compatibility to WordPress 6.1

=  Version 1.0.6  = 
- Fix: Code updated according to coding standard.

=  Version 1.0.5  = 
- Improvement: Improvements as per WordPress coding standards.

=  Version 1.0.4  = 
- Improvement: Compatibility with WordPress 5.5.

=  Version 1.0.3  = 
- Improvement: Add Username/Password API authentication method which is more reliable.

=  Version 1.0.2  = 
- Fix: Prevent fatal error when plugin incorrect response is received.

=  Version 1.0.1 = 
- Improvement: Change the Cron to update contacts count to be executed every week rather than every day, making fewer requests to Mautic
- Fix: Add correct comma separate formatting for the contacts count.

=  Version 1.0.0 = 
- Initial Release.

== Installation ==
# How To Get Mautic API Credentials 

Need help to get Mautic API credentials? Refer [this doc](https://docs.brainstormforce.com/how-to-get-mautic-api-credentials/) to know How to get mautic credentials.
