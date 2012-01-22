=== Participants Database ===
Contributors: xnau
Donate link: http://xnau.com/wordpress-plugins/participants-database
Tags: supporter, member, volunteer, database, sign up form, survey, management, non-profit, political, community, organization, mailing list, team
Requires at least: 2.8.4
Tested up to: 3.3.1
Stable tag: 1.2

Build and maintain a fully customizable database of participants or volunteers with signup forms, admin backend, custom lists, and CSV support.

== Description ==

This plugin offers all the functionality needed to build and maintain a database of people. The database is fully configurable, allowing you to define all the fields of information you want to store for each record. The records can be entered individually in the WordPress admin section, imported with a CSV file, or let the individuals themselves create their own record. Display lists of records can be sorted and filtered by any field, and the result exported as a CSV file.

This plugin was developed for an organization with the mission of educating and empowering voters. This organization needed to quickly build a database of concerned voters, supporters, and volunteers, giving them an ability to collect input and feedback, organize volunteers, and mobilize it's voter constituency with actions such as petition drives and voter education campaigns.

This database could be of use to any organization that needs to build and maintain lists of constituents, supporters, members, volunteers, etc. for any purpose. It is designed to be easy to use and serve multiple purposes, with several very powerful features to customize it's functionality to the needs of your organization, club, sports team, or any other large group of people.

**Some of the features of the Participants Database Plugin:**

* fully configurable database for holding any kind of information about people
* customizable forms for collecting and maintaining records
* both front-end and back-end versions of the forms
* shortcode for inserting a configurable sign-up short form into WordPress pages, posts, etc.
* shortcode for inserting a full-length form for people to fill out and maintain their own records
* shortcode for displaying the list on the site
* email notification and confirmation with secure individual access link
* email notification when a user edits a record
* searchable, sortable record listings in the WordPress admin
* many form elements to choose from including dropdowns, checkboxes, radio buttons, image upload, etc.
* export CSV files for interacting with other databases, mass email, print records
* import CSV files to add large numbers of records from spreadsheets such as Open Office or Google Docs

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

**List Display**

Display the list on your website with the [pdb_list] shortcode. You can determine which fields get shown, and for long lists, the list can be broken up into pages.

**Import/Export Records**

All records can be exported as a CSV-formatted text file that can be read by spreadsheet applications and used for mass email campaigns, hard-copy lists, and other applications. The records exported can be filtered by column values: for instance, only people who have consented to receive a newsletter will be included. Records may also be sorted by any column. Which fields are included in the export/import is determined by the "import" column of the field definition.

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

== Screenshots ==

1. Managing Database fields: this is where you set up your database fields and all attributes for each field
2. Edit Record: this is where an individual record can be created or edited
3. Import CSV File: page where CSV files can be imported, includes detailed instructions and a blank spreadsheet download

== Changelog ==

= 1.2.1 =
* timestamps can now be displayed in the admin record list
* added "internal" group tab to control display and export of internal fields

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

To collect signups on your website there are two shortcodes: [pdb_signup] and [pdb_record]. Use the [pdb_signup] one to give people a way to add their name to your list. You can customize which fields they need to fill in. [pdb_record] displays an expanded version of the form so people can add to or edit their own record. These two shortcodes can be on the same page if you want.

This way, your site visitors can sign up with only a name and email (for instance) and then go back and fill out all the other information you want to get from them. It is not necessary for them to log in or register, and the people in the database are separate from your WordPress site users.

When they sign in, they will be sent an email with a private link to their full record. This page is where the second [pdb_record] shortcode goes.

If you want to display the list on your website, place the [pdb_list] shortcode on any page or post. Long lists can be paginated. The fields that get displayed is set on the fields management page under the "display" column. The display can be customized with additional parameters:
* list-limit - sets the number of records to list per page (defaults to the plugin setting value)
* class - lets you set the CSS class of the container (defaults to 'participants-database')

**Quick Start Instructions:**

1. Go to the "manage fields" page and set up the fields you want to use for your database. You can also change this later, but it's best to have it all set up before you really start adding records.
2. While you are setting up your fields, look at the "signup" column. This is where you choose which fields the site visitor will have to fill out in order to sign up. The email field is required in order to email them a link to come back and fill out their full record. If you don't want them to do that, you don't have to include the email field in the signup form. You can also turn the email sending off in the settings.
3. Put the [pdb_signup] shortcode wherever you want your signup form to appear.
4. Put the [pdb_record] shortcode on the page where you want them to fill out their full record. You must go to the plugin settings page and select this page in the "Participant Record Page" dropdown.
5. When someone signs up, an email is sent to them with a private link to their record so they can fill out all the fields you want them to. This is why you need to tell the plugin where your [pdb_record] shortcode is. You can define which fields they will see there on the "manage fields" page.
6. Go to the plugin settings page to set up the emails. There are both user and admin emails sent on a signup, and you can determine the content of the emails and also whether to send them and to whom.

Of course, records can be entered in the backend by a user with "edit posts" or above capabilities. Setting up the fields and plugin settings requires an admin. Records can also be imported with a CSV file.