<?php // help file for fields management page
?>
<div class="text-block">
<h3>Manage Fields Help</h3>
<h4>Defining Database Fields</h4>
<p>The database stores information about people. On this page, you can determine what pieces of information are stored and how they are presented and interacted with. Take a look at the "Add Participant" page to see how the form is presented. You will be able to see your changes to how the fields are set up by checking this page.</p>
<p><strong>Important:</strong> Some of the changes you can make here will affect existing records in the database. It is best to set up your database before you begin adding records, but it is possible to add fields to the database at any time. Any records that were entered before the new field was added will have no value for the field. This can be filled in by then editing the record.</p>
<h4>Delete</h4>
<p>Click on the red "X" to delete a field. If there are existing records with this data field, that information will not be deleted from the database, but you will no longer be able to display or edit it.</p>
<h4>Order</h4>
<p>Click and drag to reorder the fields. You do not need to click on "Update" for the change to take effect.</p>
<h4>Title</h4>
<p>This is the display title for your field. You can change it at any time, it won't affect the field in any way other than to change how it is labeled in forms.<p>
<h4>Default</h4>
<p>If you wish to pre-load a field with a value, you can put it here.</p>
<h4>Group</h4>
<p>The database fields are organized into groups, and you can select which group the database field belongs to here. You can edit the groups under the "Field Groups" tab.</p>
<h4>Help Text</h4>
<p>When a form is displayed, sometimes it is a good idea to explain something about the information you want in a field. Any text you put in here will be displayed next to the field. HTML is allowed, but be careful with that, you can break things!</p>
<h4>Form Element</h4>
<p>If your piece of information is a simple line of text (like a name), it should be presented in the form as a text field. If a longer piece of text is called for, a text-field would be a better choice, and so forth. This is where you can determine what kind of form element this particular piece of information will be preseted with.</p>
<p>Under the multiple-choice dropdown are the form elements you can choose from. It is really best to avoid changing this once you have begun adding records to the database using this field&hellip;unpredictable things can happen, and although you won't lose any data, it can make a mess! That said, it <em>is</em> possible to change between similar elements, like text/text-field or select/dropdown or select-other/dropdown-other. Be warned, it's safest to set this up before you start getting data for the field.</p>
<h4>Values</h4>
<p>For form elements that have multiple choices, you need to define those choices here. Type in a comma-separated list of the options you want to present.</p>
<p>Don't try to have a comma in one of your options, though! It's posiible to break things by trying to include other (mostly non-text and punctuation) characters, so test things, and if you get erratic behavior in the form, you may have characters here that are messing things up. Most international language characters should be fine, just type them in. Don't try to use HTML entities or codes, it just won't work.</p>
<h4>Validation</h4>
<p>If you wish to require certain fields, you can set that here. "No" means you don't care if the field is filled in or not. "Yes" simply means the field must have something in it. "Regex" is a way to look for a specific kind of content for the field, but it is for advanced users, so if you don't know what a regex is, get some help before trying to use this feature.</p>
<h4>Column</h4>
<p>This only affects the "List Participants" page of the plugin. On that page, the database records are listed. Since it would be impractical to include every field as a column in this list, you can select which fields are shown here. "0" means don't display it, a number sets the order in which the column will appear. You have to make sure the numbers make sense, if you have multiple items with the same number (other than 0), only the last one with the number will appear.</p>
<h4>Switches</h4>
<p><strong>Sortable</strong> means the list presented on the "List Participants" page can be sorted by this field. Of course, it must have a non-zero value in "Column" to be seen at all.</p>
<p><strong>Import</strong> means the field is one that can be imported with a CSV file. If you are using CSV files to bring in records, you should set these switches based on which fields you are planning to import.<p>
<p><strong>Persistent</strong> is a convenience feature helpful when you are manually entering a series of records in the admin. If set, it means the field will carry over to the next blank entry. For instance, if a group of records from the same city are being entered, set the "city" field to be persistent, then once a city value has been entered in the first record of the series, the next blank record will have this city pre-filled.</p>
<p><strong>Signup</strong> selects this field for use in the brief signup form that is presented with the [PDb_signup] shortcode.</p>
</div>