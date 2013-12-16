<?php // help file for fields management page
?>
<div class="text-block">
<h3><?php _e('Manage Fields Help', 'participants-database' )?></h3>
<p><?php printf(__('Please visit the %sParticipants Database help pages%s for up-to-date information on using this plugin.', 'participants-database'),'<a href="http://xnau.com/participants-database/" target="_blank">','</a>' )?></p>
<h4><?php _e('Defining Database Fields', 'participants-database' )?></h4>
<p><?php _e('The database stores information about people. On this page, you can determine what pieces of information are stored and how they are presented and interacted with. Take a look at the "Add Participant" page to see how the form is presented. You will be able to see your changes to how the fields are set up by checking this page.', 'participants-database' )?></p>
<p><?php _e('<strong>Important:</strong> Some of the changes you can make here will affect existing records in the database. It is best to set up your database before you begin adding records, but it is possible to add fields to the database at any time. Any records that were entered before the new field was added will have no value for the field. This can be filled in by then editing the record.', 'participants-database' )?></p>
<h4><?php _e('Delete', 'participants-database' )?></h4>
<p><?php _e('Click on the red "X" to delete a field. If there are existing records with this data field, that information will not be deleted from the database, but you will no longer be able to display or edit it.', 'participants-database' )?></p>
<h4><?php _e('Order', 'participants-database' )?></h4>
<p><?php _e('Click and drag to reorder the fields. You do not need to click on "Update" for the change to take effect.', 'participants-database' )?></p>
<h4><?php _e('Title', 'participants-database' )?></h4>
<p><?php _e('This is the display title for your field. You can change it at any time, it won&#39;t affect the field in any way other than to change how it is labeled in forms.<p>', 'participants-database' )?>
<h4><?php _e('Default', 'participants-database' )?></h4>
<p><?php _e('If you wish to pre-load a field with a value, you can put it here.', 'participants-database' )?></p>
<h4><?php _e('Group', 'participants-database' )?></h4>
<p><?php _e('The database fields are organized into groups, and you can select which group the database field belongs to here. You can edit the groups under the "Field Groups" tab.', 'participants-database' )?></p>
<h4><?php _e('Help Text', 'participants-database' )?></h4>
<p><?php _e('When a form is displayed, sometimes it is a good idea to explain something about the information you want in a field. Any text you put in here will be displayed next to the field. HTML is allowed, but be careful with that, you can break things!', 'participants-database' )?></p>
<h4><?php _e('Form Element', 'participants-database' )?></h4>
<p><?php _e('If your piece of information is a simple line of text (like a name), it should be presented in the form as a text field. If a longer piece of text is called for, a text-field would be a better choice, and so forth. This is where you can determine what kind of form element this particular piece of information will be preseted with.', 'participants-database' )?></p>
<p><?php _e('Under the multiple-choice dropdown are the form elements you can choose from. It is really best to avoid changing this once you have begun adding records to the database using this field&hellip;unpredictable things can happen, and although you won&#39;t lose any data, it can make a mess! That said, it <em>is</em> possible to change between similar elements, like text/text-field or select/dropdown or select-other/dropdown-other. Be warned, it&#39;s safest to set this up before you start getting data for the field.', 'participants-database' )?></p>
<h4><?php _e('Values', 'participants-database' )?></h4>
<p><?php _e('For form elements that have multiple choices, you need to define those choices here. Type in a comma-separated list of the options you want to present.', 'participants-database' )?></p>
<p><?php _e('Don&#39;t try to have a comma in one of your options, though! It&#39;s posiible to break things by trying to include other (mostly non-text and punctuation) characters, so test things, and if you get erratic behavior in the form, you may have characters here that are messing things up. Most international language characters should be fine, just type them in. Don&#39;t try to use HTML entities or codes, it just won&#39;t work.', 'participants-database' )?></p>
<h4><?php _e('Validation', 'participants-database' )?></h4>
<p><?php _e('If you wish certain fields to be required or have a specific kind of content, you can set that here. "Not Required" means you don&#39;t care if the field is filled in or not. "Required" simply means the field must have something in it. "match/regex" gives you some advanced options for validating a field. To match the field to the value of another field, put the name of that field in the box. You can also use a regex to validate the field, but it is for advanced users, so if you don&#39;t know what a regex is, get some help before trying to use this feature.', 'participants-database' )?></p>
<h4><?php _e('Admin Column', 'participants-database' )?></h4>
<p><?php _e('This only affects the "List Participants" page of the plugin. On that page, the database records are listed. Since it would be impractical to include every field as a column in this list, you can select which fields are shown here. "0" means don&#39;t display it, a number sets the order in which the column will appear. You have to make sure the numbers make sense, if you have multiple items with the same number (other than 0), only the last one with the number will appear.', 'participants-database' )?></p>
<h4><?php _e('Display Column', 'participants-database' )?></h4>
<p><?php _e('When using the [pdb_list] shortcode to display the list of records, this column determines whether the column will be shown and in what order.', 'participants-database' )?></p>
<h4><?php _e('Switches', 'participants-database' )?></h4>
<p><?php _e('<strong>Sortable</strong> means the list presented on the "List Participants" page can be sorted by this field. Of course, it must have a non-zero value in "Column" to be seen at all.', 'participants-database' )?></p>
<p><?php _e('<strong>CSV</strong> means the field is one that can be imported or exported with a CSV file. If you are using CSV files to bring in records, you should set these switches based on which fields you are planning to import. When exporting a CSV, this switch determines whether the field will be exported.<p>', 'participants-database' )?>
<p><?php _e('<strong>Persistent</strong> is a convenience feature helpful when you are manually entering a series of records in the admin. If set, it means the field will carry over to the next blank entry. For instance, if a group of records from the same city are being entered, set the "city" field to be persistent, then once a city value has been entered in the first record of the series, the next blank record will have this city pre-filled.', 'participants-database' )?></p>
<p><?php _e('Persistent fields will also use their set default value in record edit forms where no value has been set in the record being edited', 'participants-database' )?></p>
<p><?php _e('<strong>Signup</strong> selects this field for use in the brief signup form that is presented with the [pdb_signup] shortcode.', 'participants-database' )?></p>
<p><?php _e('<strong>Readonly</strong> means that plugin users can see the value of the field, but cannot edit it. The field will be editable in the admin.', 'participants-database' )?></p>
</div>