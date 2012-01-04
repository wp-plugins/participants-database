=== Participants Database ===
Contributors: xnau
Donate link: http://xnau.com/wordpress-plugins/participants-database
Tags: supporter, member, volunteer, database, sign up form, survey, management, non-profit, political, community, organization, mailing list, team
Requires at least: 2.8.4
Tested up to: 3.2.1
Stable tag: 0.8.1

Build and maintain a fully customizable database of participants or volunteers with shortcodes for forms, admin backend, custom lists, and CSV import/export.

== Description ==

This plugin offers all the functionality needed to build and maintain a database of people. The database is fully configurable, allowing you to define all the fields of information you want to store for each record. The records can be entered individually in the WordPress admin section, imported with a CSV file, or let the individuals themselves create their own record. Display lists of records can be sorted and filtered by any field, and the result exported as a CSV file.

This plugin was developed for an organization with the mission of educating and empowering voters. This organization needed to quickly build a database of concerned voters, supporters, and volunteers, giving them an ability to collect input and feedback, organize volunteers, and mobilize it's voter constituency with actions such as petition drives and voter education campaigns.

This database could be of use to any organization that needs to build and maintain lists of constituents, supporters, members, volunteers, etc. for any purpose. It is designed to be easy to use and serve multiple purposes, with several very powerful features to customize it's functionality to the needs of your organization, club, sports team, or any other large group of people.

Some of the features of the Participants Database Plugin:

* fully configurable database for holding any kind of information about people
* customizable forms for collecting and maintaining records
* both front-end and back-end versions of the forms
* shortcode for inserting a configurable sign-up short form into WordPress pages, posts, etc.
* shortcode for inserting a full-length form for people to fill out and maintain their own records
* email notification and confirmation with secure individual access link
* searchable, sortable record listings in the WordPress admin
* export CSV files for interacting with other databases, mass email, print records
* import CSV files to add large numbers of records from spreadsheets such as Open Office or Google Docs

**Database**

The heart of this plugin is the participants database, which is completely configuarble. It comes pre-filled with standard fields such as name, address, phone, etc., but you can define any fields you want, including the type of field, validation, help text and a print title for each field. Fields are also organized into groups so large amounts of information can be better managed, and long forms broken up into logical sections.

Fields can be defined as text-lines, text-areas, single and multiple-select dropdowns, checkboxes or radio buttons. Each field has its own validation which can be required, not required, or validated with a regular expression.

**Sign Up Form**

The plugin provides a shortcode for a sign-up form that presents a customizable subset of the fields for a quick signup. For example, your signup form could ask only for a name and email address, creating an easy point-of-entry for new members, supporters or volunteers. The signup can generate two emails: one to an administrator to notify them of the signup, and also to the person signing up. Their email can contain a link to their full record, which they can return and fill out at their leisure. This full form (which is placed on the website with another shortcode) can include any information you want to collect from your signups.

**Frontend Form**

This is where people who have signed up can fill in any additional information about themselves you wish to collect. It can be additional demographic info, survey questions, what they would be willing to offer in support. This form is accessible to the signups via an individual link containing an ID number, which is emailed to them when they sign up. They don't need to register as a user or enter a password, they just need the link.

**Backend Form**

For your backend users, the ability to edit and enter new records is provided. This backend form can also contain administrative fields that won't be visible to the front-end (not logged-in) user, so organization staff can keep internal records of volunteer activities, availability, contributions, personal notes, etc.

The backend form is set up for rapid manual entry of multiple records, such as after a signup drive, doorbelling, or public event.

**Import/Export Records**

All records can be exported as a CSV-formatted text file that can be read by spreadsheet applications and used for mass email campaigns, hard-copy lists, and other applications. The records exported can be filtered by column values: for instance, only people who have consented to receive a newsletter will be included. Records may also be sorted by any column.

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

= USE =
[Plugin use insructions](http://wordpress.org/extend/plugins/participants-database/other_notes/)

== Use ==

This plugin has two modes of interacting with the database. First, as a tool for logged-in admin or editors to manage the database, second as a way for site visitors to sign up and fill in their information themselves. Depending on what you need, you can use either or both.

To collect signups on your website there are two shortcodes: [pdb_signup] and [pdb_record]. Use the [pdb_signup] one to give people a way to add their name to your list. You can customize which fields they need to fill in. 

This way, your site visitors can sign up with only a name and email (for instance) and then go back and fill out all the other information you want to get from them. It is not necessary for them to log in or register, and the people in the database are separate from your WordPress site users.

When they sign in, they will be sent an email with a private link to their full record. This page is where the second [pdb_record] shortcode goes.

**Quick Start Instructions:**

1. Go to the "manage fields" page and set up the fields you want to use for your database. You can also change this later, but it's best to have it all set up before you really start adding records.
2. While you are setting up your fields, look at the "signup" column. This is where you choose which fields the site visitor will have to fill out in order to sign up. The email field is required in order to email them a link to come back and fill out their full record. If you don't want them to do that, you don't have to include the email field in the signup form. You can also turn the email sending off in the settings.
3. Put the [pdb_signup] shortcode wherever you want your signup form to appear. 
4. Put the [pdb_record] shortcode on the page where you want them to fill out their full record. You must go to the plugin settings page and put the slug to this page in the "Participant Record Page" field.
5. When someone signs up, an email is sent to them with a private link to their record so they can fill out all the fields you want them to. This is why you need to tell the plugin where your [pdb_record] shortcode is. You can define which fields they will see there on the "manage fields" page.
6. Go to the plugin settings page to set up the emails. There are both user and admin emails sent on a signup, and you can determine the content of the emails and also whether to send them and to whom.

Of course, records can be entered in the backend by a user with "editor" or above capabilities. Setting up the fields and plugin settings requires an admin. Records can also be imported with a CSV file.

== Screenshots ==

1. Managing Database fields: this is where you cet up your database fields and all attributes for each field
2. Edit Record: this is where an individual record can be created or edited
3. Import CSV File: page where CSV files can be imported, includes detialed instructions and a blank spreadsheet download

== Changelog ==

= 0.8.1 =
* release version
* tested working all features

= 0.7 =
* Initial version
* No internationalization