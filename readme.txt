=== Participants Database ===
Contributors: xnau
Donate link: http://xnau.com/wordpress-plugins/participants-database
Tags: supporter, member, volunteer, database, sign up form, survey, management, non-profit, political, community, organization, mailing list, team
Requires at least: 2.8.4
Tested up to: 3.3.1
Stable tag: 1.3.7

Build and maintain a fully customizable database of participants or volunteers with signup forms, admin backend, custom lists, and CSV support.

== Description ==

This plugin offers all the functionality needed to build and maintain a database of people. The database is fully configurable, allowing you to define all the fields of information you want to store for each record. The records can be entered individually in the WordPress admin section, imported with a CSV file, or let the individuals themselves create their own record. Display lists of records can be sorted and filtered by any field, and the result exported as a CSV file.

This plugin was developed for an organization with the mission of educating and empowering voters. This organization needed to quickly build a database of concerned voters, supporters, and volunteers, giving them an ability to collect input and feedback, organize volunteers, and mobilize it's voter constituency with actions such as petition drives and voter education campaigns.

This database could be of use to any organization that needs to build and maintain lists of constituents, supporters, members, volunteers, etc. for any purpose. It is designed to be easy to use and serve multiple purposes, with several very powerful features to customize it's functionality to the needs of your organization, club, sports team, or any other large group of people.

**Some of the features of the Participants Database Plugin:**

* fully configurable database for holding any kind of information about people (or anything, really!)
* customizable forms for collecting and maintaining records
* both front-end and back-end versions of the forms
* shortcode for inserting a configurable sign-up short form into WordPress pages, posts, etc.
* completing the sign-up form can direct visitors to another page for a thank you message or reward
* shortcode for inserting a full-length form for people to fill out and maintain their own records
* shortcode for displaying the list on the site, including the ability to select and order columns to display, sorting and filtering rules to dertermine which records are shown and in what order
* email notification and confirmation with secure individual access link
* email notification when a user edits a record
* searchable, sortable record listings in the WordPress admin
* many form elements to choose from including dropdowns, checkboxes, radio buttons, image upload, etc.
* export CSV files for interacting with other databases, mass email, print records
* import CSV files to add large numbers of records from spreadsheets such as Open Office or Google Docs
* form can be organized into groups of fields, making long forms easier to navigate and fill out

**Database**

The heart of this plugin is the participants database, which is completely configurable. It comes pre-filled with standard fields such as name, address, phone, etc., but you can define any fields you want, including the type of field, validation, help text and a print title for each field. Fields are also organized into groups so large amounts of information can be better managed, and long forms broken up into logical sections.

Fields can be defined as text-lines, text-areas, single and multiple-select dropdowns, checkboxes, radio buttons or image uploads. Each field has its own validation which can be required, not required, or validated with a regular expression.

**Sign Up Form**

The plugin provides a shortcode for a sign-up form that presents a customizable subset of the fields for a quick signup. For example, your signup form could ask only for a name and email address, creating an easy point-of-entry for new members, supporters or volunteers. The signup can generate two emails: one to an administrator to notify them of the signup, and also to the person signing up. Their email can contain a link to their full record, which they can return and fill out at their leisure. This full form (which is placed on the website with another shortcode) can include any information you want to collect from your signups.

**Frontend Form**

This is where people who have signed up can fill in any additional information about themselves you wish to collect. It can be additional demographic info, survey questions, what they would be willing to offer in support. This form is accessible to the signups via an individual link containing an ID number, which is emailed to them when they sign up. They don't need to register as a user or enter a password, they just need the link.

**Backend Form**

For your backend users, the ability to edit and enter new records is provided. This backend form can also contain administrative fields that won't be visible to the front-end (not logged-in) user, so organization staff can keep internal records of volunteer activities, availability, contributions, personal notes, etc.

The backend form is set up for rapid manual entry of multiple records, such as after a signup drive, doorbelling, or public event.

For textarea fields, a rich-text editor will be used if enabled in the settings.

**List Display**

Display the list on your website with the [pdb_list] shortcode. You can determine which fields get shown, and for long lists, the list can be broken up into pages. You can specify which records get displayed and in what order. Optionally, search and sorting controls can be displayed.

**Record Display**

Each individual record can be displayed usinf a shortcode and accessed by the ID if the record. A template file formats the output of the shortcode. A plugin setting determines how a link to the individual record may be placed on the list of records.

**Import/Export Records**

All records can be exported as a CSV-formatted text file that can be read by spreadsheet applications and used for mass email campaigns, hard-copy lists, and other applications. The records exported can be filtered by column values: for instance, only people who have consented to receive a newsletter will be included. Records may also be sorted by any column. Which fields are included in the export/import is determined by the "CSV" column of the field definition.

Records can also be mass-imported with a CSV file, allowing you to use existing lists from spreadsheets, or for offline compilation of records using a spreadsheet such as Open Office or Google Docs. A blank spreadsheet can be exported from the plugin to get people started in entering records offline.

== Installation ==

1. Download the zip file, and unzip it on your computer.
2. Upload this directory (participants-database) and its contents to the `/wp-content/plugins/` directory on your web server

**or**

1. In the admin for your WordPress site, click on "add new" in the plug-ins menu.
2. Search for "participants database" in the WP plugin repository and install
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Place [pdb_record] in your blog posts and pages to show the signup form
5. Additonal features and instructions can be found on the help tab of the plugin's settings page

**Using the Plugin:**
[Plugin use insructions](http://wordpress.org/extend/plugins/participants-database/other_notes/)

== Frequently Asked Questions ==

= How do I get the form to display? Where does the shortcode go? =

Put the [pdb_signup] shortcode where you want your signup form to go.

= What happens then? =

Their record is added to the database with the information provided in the signup form. Also, two emails will be sent (if you want) one to the admin to notify them of the signup, and also to the user to tell them they've signed up and also give them a private link to their record edit page so they can update or add to their record.

= What do you mean by a users record edit page? =

This form appears on the page where you have placed the [pdb_record] shortcode. It is an expanded version of the signup form.

An individual record can be edited on the frontend of your website by someone who has the private link to that record. The purpose here is to let people maintain their own records. It's also possible to put things like survey questions in the form so you can get feedback from people. You have complete control over what fields appear on this form. Fields can be desginated as showing up on the signup form, on the frontend user's form, and on the backend admin form.

= What if I just want them to sign up and fill out all their info at that point? =

OK, just assign all the fields you want them to fill out to the signup form (this is done in the "manage fields" page). That form can be as big as you want. Then, remove the private link from the email they get when they sign up.

= How do I let people upload an image? =

Go to the "manage database fields" page and create a new field for your image. Give it a descriptive name like "avatar" or something. Click "add field" and when your new field appears, set the "field type" to "image upload" Be sure to check "signup" if you want it to appear in the signup form. When the form is presented to the user, they will be allowed to upload an image. There are a couple of settings that affect image uploads, including a file size limit.

It is possible to have multiple images per record, just add more image fields.

= When someone signs up, are they now users on my WordPress site? =

No, these signups and records are separate from people who can register on your site as users. If there are things only registered users can do (such as a forum or comments), they will have to register or sign in as usual.

The idea is to use the database as a roster of volunteers, supporters, etc. for the organization, not the website. If there is a popular call for it, I can try to make this plugin's signups also be site users, but I am not sure if it's possible.

= People are signing up, but emails are not getting sent =

Of course make sure your settings are set to send emails.

The most common reason emails are not being sent is because the WP application cannot send email. If you are having this problem, I suggest you install an SMTP plugin (like WP-Mail-SMTP) and use that plugin to test your email sending. Sometimes it is necessary to set up and use SMTP (which is not the default on most systems) to successfully send email.

= I don't see anything on the page where I put the [pdb_record] shortcode. What's up? =

The form will only appear if someone uses a valid private link to access the page. All that's required for a private link is a valid "pid" value has to be included in the URI. (it looks like "pid=TH65J" in the link) This code can be seen in the record if you want to make your own links.

= I don't want Adminstrative Fields showing up on the user's edit record page. How do I control that? =

You can control which groups of fields show up in the frontend record edit screen (the one with the [pdb_record] shortcode) by going to the "manage database fields" page, clicking on the "field groups" tab and deselecting the "display" checkbox for those field groups you don't want shown on the frontend.

= What if someone loses their private link? =

If someone does not have the private link to edit their record, they can sign up again with the same email address if "Don't Allow Duplicate Email Addresses" is checked in the plugin settings. If someone signs up with the same email address as an existing record, they will be sent the same code as before, giving them access to their previously established record. This also prevents more than one record having the same email address.

It's also possible to send them the link again in an email, but the plugin does not currently provide a way to do this. You will have to sent them a link to the edit record page (the one with the [pdb_record] shortcode), adding their code at the end of the link like this: ?pid=RH45L (using whatever the code for their record is.) The code is visible when you view the record from the "list participants" page. This is currently the only way to re-send a record edit link when unique emails are not enforced by the plugin setting.

= Can they upload files too? =

No, only image files are supported for now, primarily for security reasons.

= I'm seeing strange characters in my CSV export. What's going on? =

The plugin exports it's CSV files in "UTF-8" format. Make sure the program you're using to read the file knows this...it should detect it automatically, but can fail under some circumstances. Often this can be set on the import screen of the spreadsheet program when you open the CSV.

= Is the private link to an individual record secure? =

It is what I would call "reasonably secure" in other words, the private code in the link is not easily guessed. It can be sent in an email, which is not secure, but emails getting compromised is not that much a risk for most of us. The level of security is reasonable for the kind of information it is designed to store.

Therefore, this plugin is *absolutely not* for the storage of any kind of information requiring good security such as credit card numbers, passwords, social security numbers, etc. And I certainly couldn't be held liable if someone were to irresponsibly use the plugin for such a purpose.

= Can I make links in records clickable? =

Yes, there is a plugin setting called "Make Links Clickable" that scans the fields looking for something that starts with "http" it will then wrap that in a link tag so it will be clickable. It will also render email addresses clickable.

Even better than that, there is a new form field type called "link" that lets people fill in a URL and also give it a text label such as "My Website" that will click to the URL.

= Is a CAPTCHA available for the forms? =

There will be in the future, but for now, I'm suggesting this:

Create a text-line field with the question "what is the sum of 10 and 7?" then put in a regex to verify the answer: #^17$#

You can no doubt come up with many variations on this.

== Screenshots ==

1. Managing Database fields: this is where you set up your database fields and all attributes for each field
2. Edit Record: this is where an individual record can be created or edited
3. Import CSV File: page where CSV files can be imported, includes detailed instructions and a blank spreadsheet download

== Changelog ==

= 1.3.7 =
* fixed potential problem with timestamp fields having their datatype changed
* dates and arrays are now made readable for inclusion in emails
* improved security on list searches: prevent unwanted records from appearing
* records added in the backend are now properly timestamped

= 1.3.6 =
* better handling of search and sort functions, pagination in frontend record list
* new "strict search" setting forces exact match in user (frontend) searches on [pdb_list]
* fixed issue with select/other and multiselect/other fields not following user selects in some cases
* fixed bug that duplicated records edited in the admin if unique emails checked, email not validated and blank

= 1.3.5 =
* fixed submit bug with webkit browsers
* corrected de-capitalization of fields when auto-linking enabled
* eliminated empty IMG tags for empty image fields
* included "date_updated" and "date_recorded" fields in frontend list sorts
* removed non-displayed fields from fronent list sorting dropdown
* added "display_count" option to [pdb_list] display

= 1.3.4 =
* added sort and search field options to [pdb_list] shortcode
* added new "hidden" field for adding extra data to signups
* list shortcode filtering, sorting and display may now use internal fields
* multi-select array fields may now be exported/imported
* fixed bug in manage fields where backslashes were removed from regexes
* uploading CSV files now properly handles missing upload directory
* front-end edits no longer overwrite admin fields
* improved handling of linked URL's and email addresses
* prevent multiple submissions and multiple email notifications
* "textarea" form element now uses "text" MySQL datatype

= 1.3.3 =
* HTML emails can be switched off in settings; possible confict with other plugins
* fixed broken link to image on some systems
* fixed plugin CSS affecting rich text fields on other admin pages
* removed PHP short open code in list class file
* group name (not title) is no longer changeable: it caused orphaned fields to disappear

= 1.3.2 =
* moved templates and image uploads defaults out of the plugin directory
* updated instructions for using singe-record templates
* image files are now stored as filenames only to allow portability; backward compatibility provided for


= 1.3.1 =
* fixed error in make_link method causing broken links in installations not using rewrites
* added Italian translation by Mario of trevisoeasy.it
* removed 'internal' from list of possible field group assignments
* added 'like' operator to list filter
* made it possible to place a single record link on an image field

= 1.3 =
* added individual record shortcode using template
* several bugfixes: link element, textareas with links
* better HTML on list display table
* setting to add marker to title of required fields
* added option to use WYSIWYG editor on text areas (for logged-in WP users only)
* list shortcode can now specify which columns (fields) to show
* dates before 1970 now work

= 1.2.8.2 =
* bugfix for bobbled databse update to 0.5

= 1.2.8.1 =
* fixed bug where image files are not saved with record in some cases

= 1.2.8 =
* fixed bug with some databases not getting updated
* added setting for display text on record form

= 1.2.7 =
* bug fix: attempting to validate main form fields while on signup form

= 1.2.6 =
* bug fix for blank dates resolving to UNIX time 0

= 1.2.5 =
* added "link" form element for storing a link with link text
* added "make link" setting to make URLs clickable
* changed "import" field definition column to more appropriate "CSV"
* fixed display bug with array data from multi-select form elements
* fixed documentation error in [pdb_list] filtering section
* fixed skipped validation on thanks page redirect to new page

= 1.2.4 =
* fixed bug in [pdb_list] shorcode display filter

= 1.2.3 =
* added access level control setting for records
* added filtering and sorting to the [pdb_list] shortcode
* fixed documentation error for list shortcode: the list limit parameter must be labeled 'list_limit', not 'list-limit'
* improved handling of date values
* added greater than and less than operators to record list filters

= 1.2.2 =
* added option to show groups and group descriptions in signup form
* fixed bug in signup form submissions that contain multi-select form fields
* it is now possible to edit the titles of the internal fields

= 1.2.1 =
* timestamps can now be displayed in the admin record list
* added "internal" group tab to control display and export of internal fields
* signup form "thanks" page can now be any page
* new shortcode [pdb_signup_thanks] to display signup thank you message
* better organized settings page

= 1.2 =
* added email notifications for record updates
* new settings layout with new group for record edit form
* added ability to display the group description in the record edit form
* error message for record form called with invalid ID
* more space provided for form element select options
* database version to 0.3

= 1.1.3 =
* fixed bug with default values not preloading into the signup form
* improved handling of quotes in form fields
* better forms security
* better handing of array values and select settings for multi-select form elements

= 1.1.2 =
* fixed private record link for sites using query links
* changed "registration page" setting from text entry to page list dropdown
* split CSS files so only the needed rules are loaded by shortcodes


= 1.1.1 =
* fixed shortcode content placement issues
* added feedback message for successfully submitted [pdb_record] form
* added plugin setting for the feedback message

= 1.1 =
* added list display shortcode [pdb_list]
* added database upgrade code
* database to version 0.2
* added display column setting for frontend list display
* improved handling of quotes and apostrophes in names and titles
* changed CSV export to export only selected columns
* added 'class' parameter to signup and record shortcodes

= 1.0.1 =
* fix to issue with new records private id not getting assigned

= 1.0 =
* internationalization on all display strings
* added image upload field
* CSV export and import functions are more robust

= 0.9.6 =
* added ability to change group name
* smoother logic and JS enhancements for deleting field groups
* added "number of fields in group" column on field groups list
* fixed install db error: integrity check on nonexistent table

= 0.9.5 =
* prevent frontend record edit submission from redirecting to admin
* further CSS improvements to backend fields management page
* better default settings for field groups
* logic to allow both signup and record shortcodes to be on the same page
* setting to show the private ID and link on the admin participant listing page

= 0.9.4 =
* improved CSS on manage fields page

= 0.9.3 =
* fixed possible submit failure on non-unique email
* added unique email setting to allow multiple records with same email address
* polished CSV output for readable array data fields
* frontend edit form stays on frontend for admin user

= 0.9.2 =
* fixed new fields not getting correctly added to database in all cases
* fixed validation bug on empty validation
* set default validation to NO on new fields
* added database integrity test and fix
* fixed select disply for multi-select elements
* CSS improvements for multi-selects

= 0.9.1 =
* fixed settings object instantiation bug
* fixed problem with private id dropping off record form

= 0.9 =
* critical update
* fixed major bug in signup class
* minor security enhancements

= 0.8.1 =
* release version
* tested working all features

= 0.7 =
* Initial version
* No internationalization

== Upgrade Notice ==

= 1.3.7 =
Important bugfix and minor improvements

= 1.3.6 =
Minor improvements to [pdb_list] shortcode search and sort, bug fixes for select/other form fields

= 1.3.5 =
Bug fix release: several bugs fixed, new record total disply option for the list display

= 1.3.4 =
Important upgrade including several bug fixes, two new features and improved error handling and documentation

= 1.3.3 =
Bug fix release affecting several minor problems: image path; email plugin conflicts; admin CSS; group name change issues

= 1.3.2 =
Fixes for several minor issues; image files are now portable

= 1.3.1 =
Bugfix for sites not using rewrites; Added Italian translation

= 1.3 =
Major feature upgrade with several fixes and enhancements-- see the changelog

= 1.2.8.2 =
very important fix for all installations that installed at or after 1.2.5 

= 1.2.8.1 =
bugfux update fixing images not saving sometimes

= 1.2.8 =
minor upgrade (new button text settings for record form) and bugfix (csv exports failing on some installations)

= 1.2.7 =
Fix validation errors on signup form when record form fields are required

= 1.2.6 =
Fix display issue with blank date fields  

= 1.2.5 =
Important bug fix and feature enhancement upgrade allowing clickable links in list presentation

= 1.2.4 =
Bugfix for pdb_list shortcode, filtering with equality was failing under some circumstances

= 1.2.3 =
Important feature enhancement upgrade: added filtering and sorting to list shortcode; added an access control setting for editing, adding and viewing records; fixed problems with date filtering and sorting

= 1.2.2 =
Minor bugfix and feature upgrade; added ability to show groups and group descriptions in the signup form; multiselects in signup form bug fixed

= 1.2.1 =
Important feature upgrade includes an option to send users to a different page after signing up, and several other major enhancements for the backend

= 1.2 =
Major upgrade adds a couple of minor features including email notifications for user record updates

= 1.1.3 =
Important upgrade fixes a couple of bugs, better input values handing for forms and enhanced forms security

= 1.1.2 =
Upgrade fixes private record link for sites using query links

= 1.1.1 =
Bigfix primarily addesses content placement issues with shortcodes

= 1.1 =
Major upgrade includes new list display shorcode, more control over CSV exporting and minor improvements all around

= 1.0.1 =
Bug fix for incorrect private id assignment

= 1.0 =
Major upgrade includes internationalization, improvements to CSV import/export, and a new image upload field.

= 0.9.6 =
Important upgrade fixes 2 bugs and provides improved functionality.

== Using the Plugin ==

This plugin has two modes of interacting with the database. First, as a tool for logged-in admin or editors to manage the database, second as a way for site visitors to sign up and fill in their information themselves. Depending on what you need, you can use either or both.

Please read the documentation fully, as this is a complex plugin that can be used in a lot of different ways.

To collect signups on your website use this shortcode: [pdb_signup]. The shortcode will generate a short form to give people a way to add their name to your list. You can customize which fields they need to fill in. [pdb_record] displays an expanded version of the form so people can add to or edit their own record. These two shortcodes can be on the same page if you want. The [pdb_record] form is only available when accessed using a private link because it allows a user to edit their own record. This link can be provided in an email and is also visible in the admin listing of records.

The idea is your site visitors can sign up with only a name and email (for instance) and then go back and fill out all the other information you want to get from them. It is not necessary for them to log in or register, and the people in the database are separate from your WordPress site users.

When your site visitors fill out and submit the signup form, they will be sent an email with a private link to their full record. This page is where the [pdb_record] shortcode goes.

**Quick Start Instructions:**

1. Go to the "manage fields" page and set up the fields you want to use for your database. You can also change this later, but it's best to have it all set up before you really start adding records.
2. While you are setting up your fields, look at the "signup" column. This is where you choose which fields the site visitor will have to fill out in order to sign up. The email field is required in order to email them a link to come back and fill out their full record. If you don't want them to do that, you don't have to include the email field in the signup form. You can also turn the email sending off in the settings.
3. Put the [pdb_signup] shortcode wherever you want your signup form to appear.
4. Put the [pdb_record] shortcode on the page where you want them to fill out their full record. You must go to the plugin settings page and select this page in the "Participant Record Page" dropdown.
5. When someone signs up, an email is sent to them with a private link to their record so they can fill out all the fields you want them to. This is why you need to tell the plugin where your [pdb_record] shortcode is. You can define which fields they will see there on the "manage fields" page.
6. Go to the plugin settings page to set up the emails. There are both user and admin emails sent on a signup, and you can determine the content of the emails and also whether to send them and to whom.

Of course, records can be entered in the backend by a user with "edit posts" or above capabilities. Setting up the fields and plugin settings requires an admin. Records can also be imported with a CSV file.

**Setting Up the Signup Form "Thank You" Page**

To have your visitors go to another page after they submit a signup, go to the settings page and set the "Signup Thanks Page" setting to point to your page. Place the [pdb_signup_thanks] shortcode on that page to display the thank you message. You don't have to use the shortcode, but it gives you a way to thank them using their name.

It's also possible to use this feature to make filling out the signup form required in order to get to another (hidden) page on your website.

**Showing the List on Your Website**

You can use the [pdb_list] shortcode to show the records on your site. Just put the shortcode where you want it to show up.

Here is the full list of parameters you can use with the [pdb_list] shortcode:

* search - set to 'true' to show a list search field
* sort - set to 'true' to show a list sort field
* fields - a comma-separated list of fields to show
* list_limit - sets the number of records to list per page (defaults to the plugin setting value)
* class - lets you set the CSS class of the container (defaults to 'participants-database')
* filter - lets you determine which records are shown (see the usage notes for an explanation)
* orderby - order the list by one of the columns
* order - determines whether the list is ordered in ascending (asc) or descending (desc) order
* display_count - if set to 'true' will display a total of all records

You can set the number of records to show per page (yes, it's paginated!) with the 'list_limit' setting.

Which columns are shown by the shortcode is set up on the fields management page. For each field there is a value called "display" that determines the order of the columns in your list display. You can also set the columns to display in the shortcode by including a list of the names (not Titles!) of the fields you want to show in the order you want to show them: [pdb_list fields="last_name, city, email, photo"]

If you want to get tricky with the CSS, each header column has a class name that corresponds to the name of the field, you can use CSS to lay out the listing exactly as you want.

** Searching and Sorting The List **

You can activate list searching and/or sorting for your users to control how the list is displayed. Like this [pdb_list search="true"] or [pdb_list sort="true"] The use can select which field they want to search through a dropdown. Only fields that are displayed can be searched. For sorting, the fields offered for sorting must be checked as "sortable" and also be present in the list display.

There are two search modes for the list: strict or not strict. If the "Strict User Searching" setting (this is in the plugin settings) is checked, the search term must match *exactly* the whole contents of the field in order to be found. If unchecked, the search will show any records where any part of the searched field contains the search term.

**Filtering which Records Get Shown with the List Shortcode**

There are 3 parameters for the [pdb_list] shortcode that allow you to determine which records get shown. It's a little complicated, but here's how you do it.

There is a parameter called 'filter' which determines which records to show. The general format of the parameter is this: *field* *operator* *value* So for instance if you wanted to show all records from the state of New York, you would put [pdb_list filter='state=NY'] just like that. "state" is the field (it must match exactly the field name as listed on the mange database fields page in the plugin) "=" is the operator and "NY" is the value. The filter is grabbing all records where the state is NY.

The operators can be '=', '!', '<', '>' and '~' only. '!' means "NOT", so if you wanted everything except a certain value. '<' and '>' mean "less than" and "greater than" and can be used to compare values, like this: [pdb_list filter='last_name>k' ] That would give you all the records where the last name began with any letter coming after 'k' in the alphabet. The '=' operator looks for a strict match, and the '~' looks for a string inside the value. Careful with this one, it can give unexpected results.

You can use more than one filter by stringing them together like this: [pdb_list filter='last_name>c&last_name<h' ] (note ampersand between filter statements) This gives you all records with last names that start with d through g. Upper case or lower case doesn't matter. These comparisons can be made with dates and numbers too, of course.

To correctly compare dates, the field *must* be defined as a date field form element on the manage database fields page. Date values should be a regular date string like this: [pdb_list filter='date>jan3,2012'] It's best not to use a number for the month (and maybe you have to use English month names) because the date/month/year order is different on different servers. If you really want to use numbers for the date, just try it to see what the correct order for your web server is.

The "Strict Date Format" setting affects how the shortcode filters are interpreted, so if this is checked, the date format in your filter argument must match the date format set in your WP General Settings.

**Approving Records for Public Display**

One application for the list filtering option allows you to control whether a record gets displayed, giving you an approval process for new records. Create an administrative field (one that is not displayed or edited on the frontend) called "approved" that is a checkbox. Put this (without the quotes) in the values for the field: 'yes,no' with a default value of 'no'. Then put this in the shortcode: [pdb_list filter='approved=yes'] Now, only records that have been approved will be included in the list display. You may need to do something like this if you get a problem with spam, for instance.

**Showing an Individual Record on Your Website**

To show an individual record on the website, use the [pdb_single] shortcode. You must include the id of the record in the URL, so there must be 'pdb=1333' at the end of the page URL. The '1333' in this example would be the actual id number of the record you want to show.

You can put a link to the single record in the participant list by defining the page on which the [pdb_single] is placed, and the column where you want the link to appear. In the plugin settings, general settings tab, look for "Single Record Page": set that to the page where your [pdb_single] shortcode is. Then, go to "Single Record Link Field" and choose which field will be the link to the single record page. You can set the link on any text field or an image field. If you want to make a field that is just for linking to the record, create a field called "link" or something and then put as the default value the text you want to use for your link like "VIEW".

If you are good with CSS, you can easily make the link look like a button.

**Single Record Templates**

One of the parameters of the [pdb_single] shortcode is "template" With this, you can define a template that determines how the record is displayed. In the plugin directory in a directory called "templates" is a file called "pdb-single-default.php" if you do not specify a custom template, this file will be used. 

To make a custom template, copy this file to your own theme directory into a directory called "templates" and give it a new name like "pdb-single-mytemplate.php" This will be used if you call the shortcode with [pdb_single template="mytemplate"]. If there is a directory by that name already in your theme directory, just put the template file in there with anything else that might be in there.

In this template file is an array that can be used to prevent any fields from displaying. Let's say you wanted to exclude the city, state and postcode. In that case the array would be filled with those names like this:

$exclude = array('city','state','postcode');

These fields will now be excluded from the display. The template offers many other opportunities to customize the output of the [pdb_single] shortcode to match your theme.

**Hidden Signup Fields**

There is a new field type called "hidden" that can be used to add dynamic values to the signup form submissions. For instance, if you had the signup form in multiple locations on your blog, you could know which page the user was on when they signed up because the page name would be placed in a hidden field. 

Here is a quick tutorial to add a field that tracks the page the signup form is on:

1. Add a new field with a descriptive name, like "source page" 

2. Make that field a hidden type

3. for the default value, put "post->post_title" 

4. This will fill in the title of the page the signup form appears on. 

When the form is submitted, the information in the hidden field will be included.

At the moment, only two WordPress objects are available: $post and $current_user. You can look these up in the codex to see what properties you can access. The way this works is simple: just put the name of the property in the default value field for the hidden field. For instance "current_user->user_login" will put in the user login of a logged-in user. You don't need the dollar sign, it will ignore it if you do put it in.

If you just want to put in a fixed value, the hidden field will include any string you put into the "default" for the field.

For those with some Javascript skills, you can use these fields to store dynamic information about a signup. The hidden fields are also present in the form presented by the [pdb_record] shortcode. It is visible and editable in the admin edit page.

**Image Uploads**

Images can now be stored anywhere in the WP install directory, and can be moved without breaking image sources. The 'Image Upload Location' setting will always determine the location for the images.

IMPORTANT: Don't store images in the plugin directory (as was the default before version 1.3.2) they could be deleted by automatic upgrades. The new default location is in "wp-content/uploads/participants-database/" If you have images in the plugin directory, move them to this directory (it's a good choice, but if you know a better one, use it) and then change the plugin setting to point to the new location.

**Importing CSV Files**

CSV is a common file format for exporting databases and spreadsheets. You can export a CSV file from your spreadsheet or database and import the records into the plugin. It can be a tricky process, so I'll offer a few hints here on getting it to work.

The import will work most reliably if the CSV file has it's fields delimited by a comma and the text enclosed by double quotes. (this is actually only required for a field that contains a delimiter character) The first row should be a header row containing the column names (which are the same as your field names). The encoding of the file must be UTF-8. On the CSV import page is a button to export a blank CSV file in the correct format to get you started.

It is critical that your database or spreadsheet application be able to export the CSV in the proper format for the process to work. In some cases, you may have to open your CSV file (as exported from your spreadsheet or database) in a text editing application for further modification to get everything working. This must be a plain-text editor, don't try this with a word processor!

In particular, if your data has both double and single quotes (which are also apostrophes!) in it, it will be very difficult to import because one of the two will need to be used as the text delimiter. I suggest you convert quotes and apostrophes that are part of blocks of text into their HTML equivalents: &#39; for apostrophes and single quotes and &quot; for double quotes. This way, the text delimiter can do it's job without other characters interfering. You will probably have to use a text editing application to make these substitutions.

The "Image Upload Location" setting in the plugin is important here because that is where the plugin will place the uploaded CSV. The default setting for this is 'wp-content/uploads/participants-database/' If the plugin does not find the directory specified in the "Image Upload Location" setting, it will attempt to create it. It should notify you if it fails, and you may have to create the directory manually (using an FTP client, for instance) to get the CSV import to work.

**Plugin Support**

I do monitor the WP forums and comments page on my own website for support requests. I very much appreciate bugs being brought to my attention, so please let me know if things are broken or not working as expected.

I only ask that you read the documentation before posting with support requests because it will save us both valuable time. Many of the issues people have are due to imporoper setup or use of the plugin...this is partly due, I know, to shorcomings in the documentation. It is a complex plugin with lots of options, so it can be difficult to explain how it works in a way that everybody will understand.