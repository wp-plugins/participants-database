=== Participants Database ===
Contributors: xnau
Donate link: http://xnau.com/wordpress-plugins/participants-database
Tags: supporter, member, volunteer, database, sign up form, survey, management, non-profit, political, community, organization, mailing list, team, records
Requires at least: 3.6
Tested up to: 4.0
Stable tag: 1.5.4.8
License: GPLv2

Build and maintain a fully customizable database of participants, members or anything with signup forms, admin backend, custom lists, and CSV support.

== Description ==

This plugin offers all the functionality needed to build and maintain a database of people. The database is fully configurable, allowing you to define all the fields of information you want to store for each record. The records can be entered individually in the WordPress admin section, imported with a CSV file, or let the individuals themselves create their own record. Display lists of records can be sorted and filtered by any field, and the result exported as a CSV file.

This plugin was developed for an organization with the mission of educating and empowering voters. This organization needed to quickly build a database of concerned voters, supporters, and volunteers, giving them an ability to collect input and feedback, organize volunteers, and mobilize it's voter constituency with actions such as petition drives and voter education campaigns.

This database could be of use to any organization that needs to build and maintain lists of constituents, supporters, members, volunteers, etc. for any purpose. It is designed to be easy to use and serve multiple purposes, with several very powerful features to customize it's functionality to the needs of your organization, club, sports team, or any other large group of people.

The plugin can be easily adapted to work as a database for other applications such as indexes, directories, catalogs, or anything, really. The plugin uses a system of customizable templates for all it's displays, and an API for the customization and extension of it's capabilities. The plugin is fully internationalized with a growing set of translations.

= Some of the features of the Participants Database Plugin: =

* fully configurable database for holding any kind of information about people (or anything, really!)
* customizable forms for collecting and maintaining records
* both front-end and back-end versions of the forms
* shortcode for inserting a configurable sign-up short form into WordPress pages, posts, etc.
* completing the sign-up form can direct visitors to another page for a thank you message or reward
* shortcode for inserting a full-length form for people to fill out and maintain their own records
* shortcode for displaying the list on the site, including the ability to select and order columns to display, sorting and filtering rules to determine which records are shown and in what order
* shortcode for showing a search form that takes the user to the search results page
* email notification and confirmation with secure individual access link
* email notification when a user edits a record
* searchable, sortable record listings in the WordPress admin
* many form elements to choose from including dropdowns, checkboxes, radio buttons, image upload, rich text, etc.
* export CSV files for interacting with other databases, mass email, print records
* import CSV files to add large numbers of records from spreadsheets such as Open Office or Google Docs
* forms can be organized into groups of fields, making long forms easier to navigate and fill out

= Database =

The heart of this plugin is the participants database, which is completely configurable. It comes pre-filled with standard fields such as name, address, phone, etc., but you can define any fields you want, including the type of field, validation, help text and a print title for each field. Fields are also organized into groups so large amounts of information can be better managed, and long forms broken up into logical sections.

Fields can be defined as text-lines, text-areas, rich text (with a rich-text editor), single and multiple-select dropdowns, checkboxes, radio buttons or image uploads. Each field has its own validation which can be required, not required, or validated with a regular expression.

= Sign Up Form =

The plugin provides a shortcode for a sign-up form that presents a customizable subset of the fields for a quick signup. For example, your signup form could ask only for a name and email address, creating an easy point-of-entry for new members, supporters or volunteers. The signup can generate two emails: one to an administrator to notify them of the signup, and also to the person signing up. Their email can contain a link to their full record, which they can return and fill out at their leisure. This full form (which is placed on the website with another shortcode) can include any information you want to collect from your signups.

Signup forms are produced by a template, making it easy to add extra functionality and match the presentation of the form to your theme.

= Frontend Record Edit Form =

This is where people who have signed up can fill in any additional information about themselves you wish to collect. It can be additional demographic info, survey questions, what they would be willing to offer in support. This form is accessible to the signups via an individual link containing an ID number, which is emailed to them when they sign up. They don't need to register as a user or enter a password, they just need the link.

= Backend Record Editing =

For your backend users, the ability to edit and enter new records is provided. This backend form can also contain administrative fields that won't be visible to the front-end (not logged-in) user, so organization staff can keep internal records of volunteer activities, availability, contributions, personal notes, etc.

The backend form is set up for rapid manual entry of multiple records, such as after a signup drive, doorbelling, or public event.

For textarea fields, a rich-text editor will be used if enabled in the settings.

= List Display =

Display the list on your website with the `[pdb_list]` shortcode. You can determine which fields get shown, and for long lists, the list can be broken up into pages. You can specify which records get displayed and in what order. Optionally, search and sorting controls can be displayed. Each record listing can be linked to the full record showing all the details of the record.

= Record Display =

Each individual record can be displayed using a shortcode and accessed by the ID if the record. A template file formats the output of the shortcode. A plugin setting determines how a link to the individual record may be placed on the list of records.

= Import/Export Records =

All records can be exported as a CSV-formatted text file that can be read by spreadsheet applications and used for mass email campaigns, hard-copy lists, and other applications. The records exported can be filtered by column values: for instance, only people who have consented to receive a newsletter will be included. Records may also be sorted by any column. Which fields are included in the export/import is determined by the "CSV" column of the field definition.

Records can also be mass-imported with a CSV file, allowing you to use existing lists from spreadsheets, or for offline compilation of records using a spreadsheet such as Libre Office or Google Docs. A blank spreadsheet can be exported from the plugin to get people started in entering records offline.

= Internationalization and Translations =

This plugin is fully compliant with WordPress Internationalization standards and includes several translations, some of which are incomplete at the moment. All of the front-end text is user-customizable, so even if a translation isn't available for your language, your users will be able to use the plugin in their language.

*Translation Credits*

* French: Dominique Chapelle

* Italian: Mario Conte

* Dutch: Bas van Erp

* Polish: Piotr Kubala

* Russian: Konstantin Bashevoy [Полиатлон России](http://polyathlon-russia.com/base)

* Slovak: Branco Radenovich [WebHostingGeeks.com](http://webhostinggeeks.com/blog/)

* Norwegian: Anders Kleppe

* Serbian: Cherry, NBG, [www.trade.in.rs](http://trade.in.rs/)

* Romanian: Cornelia Năescu

* Ukranian: Michael Yunat, [http://getvoip.com/blog](http://getvoip.com)

If you are multi-lingual and feel like contributing a translation, please contact me at: support@xnau.com.

== Installation ==

1. Download the zip file, and unzip it on your computer.
2. Upload this directory (participants-database) and its contents to the `/wp-content/plugins/` directory on your web server

**or**

1. In the admin for your WordPress site, click on "add new" in the plug-ins menu.
2. Search for "participants database" in the WP plugin repository and install
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Place `[pdb_record]` in your blog posts and pages to show the signup form
5. Additonal features and instructions can be found on the help tab of the plugin's settings page

= Using the Plugin: =

This is a complex plugin that can be configured in many ways. I am happy to answer support questions, but please read the documentation I have provided. More often than not, your questions will be answered there.

I have created a [Quick Setup Guide](http://xnau.com/participants-database-quick-setup-guide/) for an example setup to get you started.

Go to the [plugin use instructions](http://wordpress.org/extend/plugins/participants-database/other_notes/) page for a more detailed explanation of the plugin's features.

== Frequently Asked Questions ==

= How do I get the form to display? Where does the shortcode go? =

Put the `[pdb_signup]` shortcode where you want your signup form to go.

= I can't find the shortcode. Where is it? =

A shortcode is a WordPress feature that lets you insert plugin content into your posts and pages. It is just a special bit of text that is replaced with the plugin content when the page or post is viewed. Just put this: `[pdb_signup]` into your page, and when you view the page, the signup form will be displayed.

= What happens when someone signs up? =

Their record is added to the database with the information provided in the signup form. Also, two emails will be sent (if you want) one to the admin to notify them of the signup, and also to the user to tell them they've signed up and also give them a private link to their record edit page so they can update or add to their record.

= What do you mean by a users record edit page? =

This form appears on the page where you have placed the `[pdb_record]` shortcode. It is an expanded version of the signup form.

An individual record can be edited on the frontend of your website by someone who has the private link to that record. The purpose here is to let people maintain their own records. It's also possible to put things like survey questions in the form so you can get feedback from people. You have complete control over what fields appear on this form. Fields can be designated as showing up on the signup form, on the frontend user's form, and on the backend admin form.

= What if I just want them to sign up and fill out all their info at that point? =

OK, just assign all the fields you want them to fill out to the signup form (this is done in the "manage fields" page). That form can be as big as you want. Then, remove the private link from the email they get when they sign up.

= How do I let people upload an image or file? =

Go to the "manage database fields" page and create a new field for your image. Give it a descriptive name like "avatar" or something. Click "add field" and when your new field appears, set the "field type" to "image upload" for images and "file-upload" for other types of file. Be sure to check "signup" if you want it to appear in the signup form. When the form is presented to the user, they will be allowed to upload an image.

There are several settings you should look at before you go live with your uploads fields. Under the "General Settings" tab, you'll find:
* File Upload Location - this tells the plugin where to put the uploaded files
* File Upload Limit - this is the size limit in Kilobytes for your file uploads. Be careful not to put this too high, if you have lots of users, it could use a lot of space on your server!
* Allowed File Types - this is a comma-separated list of allowed file extensions. You can also define this on a per-field basis.
* Allow File Delete - if this is checked, the file will be removed from the server when a user deletes it.

Each file upload field can have it's allowed files determined in the "values" field on the Manage Database Fields page. For instance, if you want them to upload only a pdf file, put "pdf" in the values field for that field. The maximum size is determined globally in the settings only.

= When someone signs up, are they now users on my WordPress site? =

No, these signups and records are separate from people who can register on your site as users. If there are things only registered users can do (such as a forum or comments), they will have to register or sign in as usual.

The idea is to use the database as a roster of volunteers, supporters, etc. for the organization, not the website.

= People are signing up, but emails are not getting sent =

Use my [Email Troubleshooting Flowchart](http://xnau.com/participants-database-email-troubleshooting/) to help you diagnose the problem.

Of course make sure your settings are set to send emails.

The most common reason emails are not being sent is because the WP application cannot send email. If you are having this problem, I suggest you install an SMTP plugin (like WP-Mail-SMTP) and use that plugin to test your email sending. Sometimes it is necessary to set up and use SMTP (which is not the default on most systems) to successfully send email.

Another common source of email trouble is other plugins that send email. It is not uncommon for such plugins to "hijack" the WP mail function and this can break it for other plugins. Try turning off other plugins that send email to see who the troublemaker is.

Finally, your emails may be getting caught in spam filters. If you find the plugin's emails in the spam folder, using a "from" email from the same domain as the site can help. If you are using HTML email (the default) keep it short and don't include lots of links, images or spammy words.

= I don't see anything on the page where I put the `[pdb_record]` shortcode. What's up? =

The form will only appear if someone uses a valid private link to access the page. All that's required for a private link is a valid "pid" value has to be included in the URI. (it looks like "pid=TH65J" in the link) This code can be seen in the record if you want to make your own links.

= I don't want Administrative Fields showing up on the user's edit record page. How do I control that? =

You can control which groups of fields show up in the frontend record edit screen (the one with the `[pdb_record]` shortcode) by going to the "manage database fields" page, clicking on the "field groups" tab and deselecting the "display" checkbox for those field groups you don't want shown on the frontend.

= I want people to provide their email when they sign up, but I don't want that information showing to the public =

It's a good practice to use field groups for something like this. Place all your "don't show to the public" fields in a group with it's "display" checkbox unchecked. This will prevent those fields from being shown in record detail pages, and also the signup form, but you can force them to display by specifying in the shortcode which groups you want included. The normally hidden groups will be included, but only those fields marked with the "signup" checkbox will appear. 

For example, let's say you have two groups: 'public' and 'private.' The email field is in the private group because you don't want it displayed. In the signup form shortcode, specify the groups to show like this: `[pdb_signup groups=public,private]` Now, both groups will be included in the signup form. Remember, only those fields marked as "signup" fields will be shown.

= I don't want group titles showing in the forms, how do I do that? =

The easiest way to do this is to simply blank out the title for the group.

= What if someone loses their private link? =

You can show a "Resend Private Link" link on your signup form, just check "Enable Lost Private Link" under the "Retrieve Link Settings" tab. You must define which field is used to identify the record. This must be a unique identifier, usually an email address, but it could be anything. The rest of the settings for this feature are under that tab.

It's also possible to send them the link again in an email, but the plugin does not currently provide a way to do this. You will have to sent them a link to the edit record page (the one with the `[pdb_record]` shortcode), adding their code at the end of the link like this: ?pid=RH45L (using whatever the code for their record is.) The code is visible when you view the record from the "list participants" page.

= Is it possible for users to upload files? =

File uploads use the "file upload" field type. You should define a set of allowed file extensions in the settings: "allowed file types" under the "general settings" tab.

= My site is not in English and searches using non-English characters are not working properly. =

If you have a non-English site, you should convert your database to the correct "collation" for your language. 

= I'm seeing strange characters in my CSV export. What's going on? =

The plugin exports it's CSV files in "UTF-8" format. Make sure the program you're using to read the file knows this...it should detect it automatically, but can fail under some circumstances. Often this can be set on the import screen of the spreadsheet program when you open the CSV.

= Is the private link to an individual record secure? =

It is what I would call "reasonably secure" in other words, the private code in the link is not easily guessed. It can be sent in an email, which is not secure, but emails getting compromised is not that much a risk for most of us. The level of security is reasonable for the kind of information it is designed to store.

Therefore, this plugin is *absolutely not* for the storage of any kind of information requiring good security such as credit card numbers, passwords, social security numbers, etc. And I certainly couldn't be held liable if someone were to irresponsibly use the plugin for such a purpose.

= Can I make links in records clickable? =

Yes, there is a plugin setting called "Make Links Clickable" that scans the fields looking for something that starts with "http" it will then wrap that in a link tag so it will be clickable. It will also render email addresses clickable.

There is also a form field type called "link" that lets people fill in a URL and also give it a text label such as "My Website" that will click to the URL.

= Is a CAPTCHA available for the forms? =

You can define a "captcha" form element which will show a simple math question for the user to answer.

== Screenshots ==

1. Managing Database fields: this is where you set up your database fields and all attributes for each field
2. Edit Record: this is where an individual record can be created or edited
3. Import CSV File: page where CSV files can be imported, includes detailed instructions and a blank spreadsheet download

== Changelog ==

= 1.5.4.9 =

* security patch for CSV download
* added Ukranian translation

= 1.5.4.8 =

* compatibility with WP 3.9 and PHP 5.5
* plugin admin menu visibility now controlled by plugin admin roles

= 1.5.4.7 =

* fixed checkbox lock bug

= 1.5.4.6 =

* fixed transaction errors when MySQL is in a strict mode
* checkboxes may now use value titles
* AJAX search response now uses template defined in the shortcode

= 1.5.4.5 =

* added otherselect.js to handle dropdown/other fields
* fixed bug in dropdowns when value is numeric 0

= 1.5.4.4 =

* readonly displays for dropdowns, radios and multiselects
* record updates leave private ID unchanged
* new setting to enable alternative sessions management if PHP sessions is not working
* fixed bug in PDbTemplate class that would return empty fields in a list

= 1.5.4.3 =

* undeclared property $readonly_fields error (this time for sure!)

= 1.5.4.2 =

bug fixes:

* undeclared property $readonly_fields error
* record updates not getting timestamp set
* problem with list search results not coming in in some cases
* readonly fields in form context now have "readonly" attribute instead of "disabled"
* record form now shows captcha if named in the shortcode "fields" attribute
* checkbox series now completely wrapped in checkbox group wrapper

Added Serbian translation

= 1.5.4.1 =

* field group tabs use group name if no title is defined for the group
* HTML entities can be used in all field option ("values") definitions
* fixed long field/group name bug. Names can be up to the maximum 64 characters
* cleaned up plugin function spillover into other admin pages
* better compatibility with pre-3.8 WP installs
* signup and record shortcodes won't try to validate unincluded fields
* all form submissions are validated for all users except plugin admin in the admin section
* in admin, last used settings now retained: sort field, sort order, search field, search operator
* better support for multi-page forms, user can't complete form by going back to the first page
* links and other HTML now allowed in field titles and help text
* field option titles used in all contexts


= 1.5.4 =

* more visual compatibility tweaks with 3.8 WP admin redesign, dashicons
* bug fixes
* plugin classes are now only included on pages with plugin shortcodes
* settings page feedback messages working
* fixed possible class/function collision with other plugins using WP_Session
* now compatible with HTML5 form element types
* bulletproofing, collision avoidance


= 1.5.3 =
** BETA RELEASE **

* admin compatible with WP 3.8
* plugin no longer relies on PHP sessions
* better compatibility with international characters in form validation
* fixed datetime bug with missing server timezone value
* updated POT file, internationalization complete
* added Norwegian translation
* bug fixes

= 1.5.2 =

** BETA RELEASE **
second round of bug fixes:

* absolute image URIs will now display correctly
* frontend list sort preferences are now heeded
* 'the_content' filter applied to rich text if enabled in settings
* improved link field data handling on import, doesn't require valid URL
* missing results on AJAX search bug fixed
* values of zero no longer considered empty
* date fields now stored as BINGINT datatype so it can be sorted correctly
* language files recompiled to correctly show selectors in the manage database fields page
* zero-division bug in Pagination fixed

= 1.5.1 =

** BETA RELEASE **
first round of bug fixes:

* frontend record edit submissions going to admin
* list_limit cannot be set to override pagination
* restored legacy public method Participants_Db::get_image_url()

= 1.5 =

**BETA RELEASE**

Please back up before installing

For critical production sites I recommend you try this new plugin version first in a development site.

* complete overhaul of the entire plugin
* new classes to handle templating and plugin updates
* added infrastructure for add-on and premium plugins
* dozens of bug fixes and code hardening for more reliable performance in your particular installation

**New Features:**

* **file upload field** allows any type of file to be uploaded
* **resend private link** for users who've lost theirs
* **math captcha** sets a simple test for a human user
* **custom CSS setting** for easy presentation tweaks
* **wildcard characters** allowed in searches
* **total shortcode** shows total records and more
* **search shortcode** to place search controls anywhere
* **groups attribute in shortcodes** to show only selected groups of fields
* **date range filters** in the admin list
* **expanded API** for more ways to customize functionality
* **“busy” spinner** image for AJAX-powered searches so the user knows something is happening while the data loads
* **labeled selection items** for better readability

= 1.4.9.3 =
* reworked class autoloading to avoid conflicts
* 'list_query' filter now happens before the length of the list is calculated so if it is altered, the length will be correct
* 'list_query' filter no longer includes the pagination limit statement

= 1.4.9.2 =
* improved date formatting for numeric non-American style dates (dd/mm/yyyy)
* fields omitted from the signup form are no longer validated, making it easier to construct different signup forms
* more control over search/sort dropdowns in the list display (see template)
* signup, record and single shortcodes now have the "fields" attribute
* list shortcode filters now correctly filter multi-select fields
* lists may now be sorted by more than one field in the shortcode
* list shortcode filter statements may now include "or" operators
* read-only text fields show the default value when empty
* adds several API hooks and filters

= 1.4.9.1 =
Taking defensive precautions against other plugins in the admin:
* admin.css is more specific about styling tabs
* no longer using .ui-tabs-hide class to show/hide tabs

= 1.4.9 =
* single record link doesn't get wrapped with anchor tag in some cases; works reliably now
* script handles were conflicting with some other plugins; script handles are now namespaced
* admin menu hook was conflicting with some other plugins; admin menu hooks are now more specific
* readonly fields were erased when record was edited in the frontend
* all date displays are now internationalized
* page links with some post types were incorrect; now using "get_permalink()" for all page links
* PDb_Pagination class was conflicting with some other plugins; renamed to PDb_Pagination
* checkbox fields now allowed for single page link
* default images are now not given full-size link
* checkbox fields now allowed for single page link

= 1.4.8 =
* readonly date fields no formatted
* better handling of multivalue fields
* internal date fields now correctly formatted
* improved safeguards against JS code collisions on the admin pages
* bug where hidden fields cause other fields to be dropped from the display fixed
* better handling of hidden dynamic values
* AJAX list filtering no longer loses pagination element

= 1.4.7 =
* internationalized dates are now displaying consistenyly on all screens
* email headers are now set on a per-message basis to avoid conflicts with text-only emails
* several bug fixes relating to date localization
* added Slovak translation by Branco Radenovich

= 1.4.6 =
* added image delete checkbox (doesn't delete file, only database reference)
* image handle file validation avoids costly CURL calls in validating files
* improved list AJAX javascript
* fixed admin page name conflict with some plugins
* international characters now work properly in user searches
* internationalization of date display
* image uploads now won't overwrite existing files of the same name by adding an index to the name

= 1.4.5.2 =
* fixes several issues brought up by the WP 3.5 release:
* admin section tabs
* plugin icon
* $wpdb->prepare new regimen

= 1.4.5.1 =
* bugfix for admin list javascript bug that deletes all records on a page if you try to delete a record

= 1.4.5 =
* AJAX search/sort internationalization
* fixed pagination issues with WP query-string page links
* improvements and expanded commenting in pdb-list-detailed template
* added database update failsafe to ensure database is in sync

= 1.4.4 =
* improvements to single record display template and stylesheet; less likely to break
* better notations and help text
* several minor bugfixes
* uploaded images and files pathing is now harder to break
* AJAX list searches are now compatible with pagination

= 1.4.3 =
* fixed bug that prevented a new uploads directory from being created in some cases
* it is now possible to have two different list shortcodes on the same page
* using WP auto formatting is now optional on rich-text fields
* fixed incompatibility with PHP 5.4

= 1.4.2 =
* fixes for several reported bugs

= 1.4 =
* now using templates for all shortcode output
* added 'read only' attribute for fields
* added random sort for list output
* added "match other field" validation option for field double-checks
* added default sort order for the admin list
* hidden fields can now capture cookie values as well as server values, WP user data, etc.
* placeholder tags may now be used in email subject lines
* new form element: "Rich Text" a textarea element with a rich text editor
* new form element: "Password" stored as a WP-compatible hash
* added 'search results only' functionality for list shortcode
* list searches now update the list without reloading the page (using AJAX)
* you can define a "default image" to show when no image has been uploaded into a record
* CSV export now requires admin privileges
* improved handling of rich text content displays

== Upgrade Notice ==

= 1.5.4.9 =

security update

== Using the Plugin ==

* General Description
* Quick Start Instructions
* List of Plugin Shortcodes
* The List Shortcode
* Shortcode Templates
* The Thank You Page
* Hidden Signup Fields
* Uploading Images
* Importing Records

This plugin has two modes of interacting with the database. First, as a tool for logged-in admin or editors to manage the database, second as a way for site visitors to sign up and fill in their information themselves. Depending on what you need, you can use either or both.

*Please read the documentation fully, as this is a complex plugin that can be used in a lot of different ways.*

To collect signups on your website use this shortcode: `[pdb_signup]`. The shortcode will generate a short form to give people a way to add their name to your list. You can customize which fields they need to fill in. 

When they sign up, they can be provided with a private link to their record, which the can add to or edit.  Place the `[pdb_record]` shortcode on they page you want them to go to to edit their record. This page should also be selected in the settings as the "Participant Record Page."

The idea is your site visitors can sign up with only a name and email (for instance) and then go back and fill out all the other information you want to get from them. It is not necessary for them to log in or register, and the people in the database are separate from your WordPress site users.

When your site visitors fill out and submit the signup form, they will be sent an email with a private link to their full record. This page is where the `[pdb_record]` shortcode goes. <a id="quickstart"></a>

= Quick Start Instructions: =

1. Go to the "manage fields" page and set up the fields you want to use for your database. You can also change this later, but it's best to have it all set up before you really start adding records.
2. While you are setting up your fields, look at the "signup" column. This is where you choose which fields the site visitor will have to fill out in order to sign up. The email field is required in order to email them a link to come back and fill out their full record. If you don't want them to do that, you don't have to include the email field in the signup form. You can also turn the email sending off in the settings.
3. Put the `[pdb_signup]` shortcode wherever you want your signup form to appear.
4. Put the `[pdb_record]` shortcode on the page where you want them to fill out their full record. You must go to the plugin settings page and select this page in the "Participant Record Page" dropdown.
5. When someone signs up, an email is sent to them with a private link to their record so they can fill out all the fields you want them to. This is why you need to tell the plugin where your `[pdb_record]` shortcode is. You can define which fields they will see there on the "manage fields" page.
6. Go to the plugin settings page to set up the emails. There are both user and admin emails sent on a signup, and you can determine the content of the emails and also whether to send them and to whom.

Of course, records can be entered in the backend by a user with "edit posts" or above capabilities. Setting up the fields and plugin settings requires an admin. Records can also be imported with a CSV file. <a id="shortcodes"></a>

= Shortcodes this Plugin Uses =

This plugin uses a number of different shortcodes to place it's functionality in your content. Here is a breakdown of all the codes:

1. `[pdb_signup]` - This prints out the signup form including only those fields selected for inclusion. On submission, it creates a new record and optionally notifies the admin and the person signing up by email.
2. `[pdb_record]` - This shortcode only displays when accessed with a URL including the record "private id" number. It allows the user to edit and add to their own record, while others won't have access to it. A private link to this record can be included in the "thankyou" email they will receive on submission.
3. `[pdb_list]` - This shortcode prints out a list of records from the database. Which records get displayed and how can all be determined by the admin. It is also possible to allow the site visitor to filter (search) the list and also sort it.
4. `[pdb_single]` - This shows a single record as determined by the record ID present in the URL used to access the page. It is possible to set a link to the single record in the record list, allowing for a detail display of a single record.
5. `[pdb_signup_thanks]` - If you want to send the people who use the signup form to another page after they submit, you can use this shortcode to display the thankyou message. This is not needed if you have them just go back to the signup page, the signup shortcode will take care if it. <a id="list"></a>
6. `[pdb_search]` - will show a search form which can be placed anywhere. When the search is performed, the user is taken to a page with a [pdb_list] shortcode and shown the result of their search.
7. `[pdb_request_link]` - shows a form for requesting the private link to be resent.
8. `[pdb_total]` – shows the either the number of records matching a given filter or the numeric total of a given column of values.

= Showing a List of Records on Your Website =

You can use the `[pdb_list]` shortcode to show the records on your site. Just put the shortcode where you want it to show up.

Here is the full list of parameters you can use with the `[pdb_list]` shortcode with an example of it's use:

* search - set to 'true' to show a list search field `[pdb_list search="true"]`
* sort - set to 'true' to show a list sort field `[pdb_list sort="true"]`
* fields - a comma-separated ordered list of fields to show (defaults to the fields display column setting) `[pdb_list fields="first_name,last_name,city,state"]`
* list_limit - sets the number of records to list per page (defaults to the plugin setting value) `[pdb_list list_limit="5"]`
* class - lets you set the CSS class of the container (defaults to 'participants-database') `[pdb_list class="full-list centered"]`
* filter - lets you determine which records are shown (see the usage notes for an explanation) `[pdb_list filter="event_date>oct 13,2011&state=NY"]`
* orderby - order the list by one of the columns `[pdb_list orderby="event_date"]` It's also possible to order the list randomly with `orderby=random`
* order - determines whether the list is ordered in ascending (asc) or descending (desc) order `[pdb_list order="asc"]`
* display_count - if set to 'true' will display a total of all records displayed `[pdb_list display_count="true"]`

All of these can be combined into one shortcode, for instance: `[pdb_list fields="first_name,last_name,city,state,event_date" search="true" sort="true" filter="event_date>oct 13,2011&state=NY"]`

You can set the number of records to show per page (yes, it's paginated!) with the 'list_limit' setting.

Which columns are shown by the shortcode is set up on the fields management page. For each field there is a value called "display" that determines the order of the columns in your list display. You can also set the columns to display in the shortcode by including a list of the names (not Titles!) of the fields you want to show in the order you want to show them: `[pdb_list fields="last_name,city,email,photo"]`

If you want to get tricky with the CSS, each header column has a class name that corresponds to the name of the field, you can use CSS to lay out the listing exactly as you want.

= Searching and Sorting The List =

You can activate user list searching and/or sorting for your users with these attributes: `[pdb_list search=true]` or `[pdb_list sort=true]` These attributes will enable a search form and/or sort form displayed above the list. The user can select which field they want to search through a dropdown and type in a search term. Only fields that are displayed can be searched. For sorting, the fields offered for sorting must be checked as "sortable" and also be present in the list display.

User list searches now allow for the use of the '*' wildcard, so for instance, to search for records beginning with the letter 'B' the search would be 'B*'.

There are two search modes for the list: strict or not strict. If the "Strict User Searching" setting (this is in the plugin settings) is checked, the search term must match *exactly* the whole contents of the field in order to be found. If unchecked, the search will show any records where any part of the searched field contains the search term.

It's also possible to perform a "search" on the list with parameters in the URL. This would be handy if you wanted to create a link to the list that would only show certain results. For instance, if you wanted to make a link to the list that showed only results from the city of Phoenix you would add these variables to the URL: 
`?search_field=city&value=phoenix&submit=search` 
The URL must include the 'submit' variable to perform the search when the link is opened. Add that to the end of a link to the page where the `[pdb_list]` shortcode is (replace the '?' with a '&' if the '?' is already there), and it will only show records with a city value of 'phoenix.' This also means you can create your own search functionality, the primary limitation being you can only do this for one field at a time.

Please note that user list searching, sorting and pagination will not work properly if there is more than one list on a page. The shortcode filtering and sorting will work as expected, however.

= Filtering which Records Get Shown with the List Shortcode =

There are 3 parameters for the `[pdb_list]` shortcode that allow you to determine which records get shown. It's a little complicated, but here's how you do it.

There is a parameter called 'filter' which determines which records to show. The general format of the parameter is this: *field* *operator* *value* So for instance if you wanted to show all records from the state of New York, you would put `[pdb_list filter='state=NY']` just like that. "state" is the field (it must match exactly the field name as listed on the mange database fields page in the plugin) "=" is the operator and "NY" is the value. The filter is grabbing all records where the state is NY.

The operators can be '=', '!', '<', '>' and '~' only. '!' means "NOT", so if you wanted everything except a certain value. '<' and '>' mean "less than" and "greater than" and can be used to compare values, like this: `[pdb_list filter='last_name>k' ]` That would give you all the records where the last name began with any letter coming after 'k' in the alphabet. The '=' operator looks for a strict match, and the '~' looks for a string inside the value. Careful with this one, it can give unexpected results.

You can use more than one filter by stringing them together like this: `[pdb_list filter='last_name>c&last_name<h' ]` (note ampersand between filter statements) This gives you all records with last names that start with d through g. Upper case or lower case doesn't matter. These comparisons can be made with dates and numbers too, of course.

It's now also possible to use an "or" operator to link statements, for instance if you wanted to show all records with a city of Boston or Providence, you would use `[pdb_list filter="city=boston|city=providence"]` and they can be combined: `[pdb_list filter="last_name=smith&city=boston|city=providence"]`

To correctly compare dates, the field *must* be defined as a date field form element on the manage database fields page. Date values should be a regular date string like this: `[pdb_list filter='date>jan3,2012']` It's best not to use a number for the month because the date/month/year order is different on different servers.

The "Strict Date Format" setting affects how the shortcode filters are interpreted, so if this is checked, the date format in your filter argument must match the date format set in your WP General Settings.

= Numeric Sorting =

By default all sorting of lists is done alphabetically, which means that if you have a field that is numeric (like prices or rating points) you will not get a numeric sort if you use that field to sort the list. If you need to do this, you must manually edit the database to make your numeric field work like a number and not a word. Here's how you do that:

Get into your database using PHPMyAdmin or similar application. Click on the structure tab of the 'participants_database' table. This shows you the types of all the fields that are used to store the data. You will recognize the names, and most of them will be of type TINYTEXT. You need to change the field type for only the fields where your numbers are stored.

Click on the little pencil to edit the field where you have numbers. You need to decide the range of numbers you will be using. Lets say you need to be able to go up to 9999 with two decimal places. Change the type from TINYTEXT to DECIMAL. In the LENGTH/VALUES field, put the size of number you want to use...our example would be 4,2 giving you up to four figures to the left of the dot and two to the right. Save it and now your sorts on that field will be numeric. This also applies to comparisons in the shortcode filter.

Please note that non-numeric characters cannot be stored in a DECIMAL field, including commas and currency symbols.

= Only Showing List Items When a User Searches =

You can set up the list so no listings will show until a user makes a search. Just add a filter for id=0, like this: `[pdb_list filter="id=0"]` since there is no record with that ID, nothing will show. The user search will override that and show the result of their search, but this is a special case.

Normally, a user search can only override the fields they search on. For instance, if you set your list to show only listings from a certain city, and a user searches for a name, the search results will find the name they searched for and also continue to be filtered by the city. If they search by city, their search will override the city filter in the shortcode.

= Approving Records for Public Display =

One application for the list filtering option allows you to control whether a record gets displayed, giving you an approval process for new records. Create an administrative field (one that is not displayed or edited on the frontend) called "approved" that is a checkbox. Put this (without the quotes) in the values for the field: 'yes,no' with a default value of 'no'. Then put this in the shortcode: `[pdb_list filter='approved=yes']` Now, only records that have been approved will be included in the list display. You may need to do something like this if you get a problem with spam, for instance.<a id="templates"></a>

= Shortcode Templates =

The list, record, single and signup shortcodes use templates to determine how they are presented. Plugin default templates are found in the "templates" directory in the plugin directory. These templates are commented to help you understand how they work, and are intended to serve as starting points to creating your own custom templates. Like WordPress theme templates, some knowledge of PHP, CSS and HTML is required to create and edit them.

If you want to customize a template, use an FTP program to copy the template file you want to use as a starting point to a "templates" directory in your theme:  `wp-content/themes/your_theme_name/templates/`. Create the directory if it doesn't exist; if it does exist, just place the the template file in there.

IMPORTANT: if you modify the templates in the shortcode directory, your changes will be lost when you upgrade the plugin. Copy them to your theme directory in a directory named "templates." The path will look something like this: `(WP ROOT)/wp-content/themes/your_theme_name/templates/pdb-signup-customtemplate.php`

Template files use this naming convention: pdb-{shortcode name}-{template name}.php. So, for instance, the default template for the `[pdb_signup]` shortcode is "pdb-signup-default.php" If you want to create your own template file, you would rename it something like: "pdb-signup-mytemplate.php" then, call it in the shortcode like this: `[pdb_signup template="mytemplate"]`.

I have created a help page for custom templates which includes a tutorial: http://xnau.com/pdb-templates/

= Single Record Templates =

Templates for the single record shortcode include an array that can be used to prevent any fields from displaying. Let's say you wanted to exclude the city, state and postcode. In that case the array would be filled with those names like this:

`$exclude = array('city','state','postcode');`

These fields will now be excluded from the display. The template offers many other opportunities to customize the output of the `[pdb_single]` shortcode to match your theme.

= Showing an Individual Record on Your Website =

To show an individual record on the website, use the `[pdb_single]` shortcode. You must include the id of the record in the URL, so there must be 'pdb=1333' at the end of the page URL. The '1333' in this example would be the actual id number of the record you want to show.

You can put a link to the single record in the participant list by defining the page on which the `[pdb_single]` is placed, and the column where you want the link to appear. In the plugin settings, general settings tab, look for "Single Record Page": set that to the page where your `[pdb_single]` shortcode is. Then, go to "Single Record Link Field" and choose which field will be the link to the single record page. You can set the link on any text field or an image field.

If you want to make a field that is just for linking to the record, create a field called "link" or something and then put as the default value the text you want to use for your link like "VIEW". Set the field to "read only" so it can't be edited.

If you are good with CSS, you can easily make the link look like a button.

= Determining Which Fields are Shown by Each Shortcode =

Each of the 4 main shortcodes (signup, record, list and single) can show any or all of the fields you have defined, depending on the settings on the "manage database fields" page. For the signup, record and single shortcodes, only fields in groups marked "display" will be shown. The signup shortcode also only shows fields that have "signup" checked. The list shortcode only shows fields that have a non-zero "display" value.

Each of these 4 shortcodes can also determine which fields to show in the shortcode itself by using the "fields" attribute. This attribute is a comma-separated list of field names to show in the shortcode. This makes it easy to have different sets of fields appear on different pages, or to create different signup forms for different pages. For example: `[pdb_signup fields="first_name,last_name,address,city,country,email,newsletter"]`

By using custom templates, you can have even more control over how things are displayed by the shortcode. These templates are very similar to the templates that a WordPress theme uses. <a id="thankyou"></a>

= Setting Up the Signup Form "Thank You" Page =

To have your visitors go to another page after they submit a signup, go to the settings page and set the "Signup Thanks Page" setting to point to your page. Place the `[pdb_signup_thanks]` shortcode on that page to display the thank you message. You don't have to use the shortcode, but it gives you a way to thank them using their name.

It's also possible to use this feature to make filling out the signup form required in order to get to another (hidden) page on your website.<a id="hidden"></a>

= Hidden Signup Fields =

There is a field type called "hidden" that can be used to add dynamic values to the signup form submissions. For instance, if you had the signup form in multiple locations on your blog, you could know which page the user was on when they signed up because the page name would be placed in a hidden field. 

Here is a quick tutorial to add a field that tracks the page the signup form is on:

1. Add a new field with a descriptive name, like "source page" 

2. Make that field a hidden type

3. for the default value, put "post->post_title" (without the quotes)

4. This will fill in the title of the page the signup form appears on. 

When the form is submitted, the information in the hidden field will be included.

At the moment, two WordPress objects are available: $post and $current_user. You can look these up in the codex to see what properties you can access. The way this works is simple: just put the name of the property in the default value field for the hidden field. For instance "current_user->user_login" will put in the user login of a logged-in user. You don't need the dollar sign, it will ignore it if you do put it in.

If you just want to put in a fixed value, the hidden field will include any string you put into the "default" for the field.

You can also access some PHP "Superglobals." For instance, to get the IP of the user, put this into the default: "SERVER:REMOTE_ADDR" You can access these superglobals: POST, GET, REQUEST, COOKIE, SERVER, SESSION. This means you can get the value of a cookie and put it into the form, saving it when the form is submitted. You don't need the '$_' that is used in PHP. A good reference for what is possible can be found here: <http://php.net/manual/en/language.variables.superglobals.php>

For those with some Javascript skills, you can use these fields to store dynamic information about a signup. The hidden fields are also present in the form presented by the `[pdb_record]` shortcode. These fields are visible and editable in the admin edit page.<a id="images"></a>

= Image & File Uploads =

Files can be uploaded and managed using the "File Upload" field type. The allowed filetypes (determined by extension) are defined in the "Allowed File Types" setting. This is a global setting for all uploads. Image files are uploaded and managed using the "Image Upload" field type. If you use a "File Upload" type field for image uploads, the image itself won't show in listings, only the filename.

Uploaded images and files can be stored anywhere in the WP install directory, and can be moved without breaking image sources. The 'File Upload Location' setting will always determine the location for files uploaded using the plugin forms. The files are stored in the database as filenames without any path information.

The plugin has a "default image" that will be shown if no image is present. You can create your own, but you shouldn't keep it in the plugin folder. Keep it in the uploads directory or in your theme directory. Change the "Default Image" setting to point to your image. If you don't want anything to show if no image is present, make the "Default Image" setting blank.

IMPORTANT: Don't store your files in the plugin directory, they will be deleted by automatic upgrades. The new default location is in "wp-content/uploads/participants-database/" If you have files in the plugin directory, move them to this directory (it's a good choice, but if you know a better one, use it) and then change the plugin setting to point to the new location.

File uploads can present a security risk to a website, so some precautions should be taken. The plugin secures uploads by replacing the filename of the uploaded file with a sanitized filename. We are depending on the hosting server to prevent the possibility of executing an uploaded file based on the filename extension.<a id="csv" name="csv"></a>

= Importing CSV Files =

CSV is a common file format for exporting databases and spreadsheets. You can export a CSV file from your spreadsheet or database and import the records into the plugin. It can be a tricky process, so I'll offer a few hints here on getting it to work.

The import will work most reliably if the CSV file has it's fields delimited by a comma and the text enclosed by double quotes. (this is actually only required for a field that contains a delimiter character) The first row should be a header row containing the column names (which are the same as your field names). The encoding of the file must be UTF-8. On the CSV import page is a button to export a blank CSV file in the correct format to get you started.

It is critical that your database or spreadsheet application be able to export the CSV in the proper format for the process to work. In some cases, you may have to open your CSV file (as exported from your spreadsheet or database) in a text editing application for further modification to get everything working. This must be a plain-text editor, don't try this with a word processor!

In particular, if your data has both double and single quotes (which are also apostrophes!) in it, it will be very difficult to import because one of the two will need to be used as the text delimiter. I suggest you convert quotes and apostrophes that are part of blocks of text into their HTML equivalents: &#39; for apostrophes and single quotes and &quot; for double quotes. This way, the text delimiter can do it's job without other characters interfering. You will probably have to use a text editing application to make these substitutions.

The "Image Upload Location" setting in the plugin is important here because that is where the plugin will place the uploaded CSV. The default setting for this is 'wp-content/uploads/participants-database/' If the plugin does not find the directory specified in the "Image Upload Location" setting, it will attempt to create it. It should notify you if it fails, and you may have to create the directory manually (using an FTP client, for instance) to get the CSV import to work.

If you are importing a CSV into a database that has records in it, then there are three modes for that you can use, determined by the "Duplicate Record Preference" under the Signup tab. You can choose to import all records as new records, leaving the existing records alone. Or-- by choosing a field to match, either update the existing records with the uploaded information or skip importing any record that matches an existing record. The field you choose for a match should be a unique field...if it is not, messy, unpredictable things can happen.

= Plugin Support =

I do monitor the WP forums and comments page on my own website for support requests. I very much appreciate bugs being brought to my attention, so please let me know if things are broken or not working as expected.

I only ask that you read the documentation before posting with support requests because it will save us both valuable time. Many of the issues people have are due to improper setup or use of the plugin...this is partly due, I know, to shortcomings in the documentation. It is a complex plugin with lots of options, so it can be difficult to explain how it works in a way that everybody will understand.