<?php
/*
 *
 * template for participants list search form
 *
 */
?>
<div class="wrap <?php echo $this->wrap_class ?>">

  <div class="pdb-searchform">

    <?php 
    /* this element is where error and feedback messages are shown to the user.
     * It is only visible if there is a message
     * 
     * don't change the classnames or IDs, they are requted for the error messagees 
     * to show up properly
     * do change the strings in the "_e" functions for your message preference--or 
     * replace the contents of the p tag with your error messages
     */ ?>
    <div class="pdb-error pdb-search-error" style="display:none">
      <p class="search_field_error"><?php _e('Please select a column to search in.', 'participants-database') ?></p>
      <p class="value_error"><?php _e('Please type in something to search for.', 'participants-database') ?></p>
    </div>

    <?php
    /* this method prints the top of the search/sort form
     */
    $this->search_sort_form_top();
    ?>

    <?php if ($filter_mode == 'filter' || $filter_mode == 'both') : ?>

      <fieldset class="widefat">
        <legend><?php _e('Search', 'participants-database') ?>:</legend>

        <?php
        /*
         * there are 4 options for this function which defines which fields will be 
         * available in the search dropdown selector:
         *    1. "all fields" text: set the text of the default "all fields" item: 
         *       leave it "false" to use the internationalized defualt
         *    2. print it or return the element as a value: only use this if you 
         *       need to alter the HTML directly. You will need to print the item for 
         *       it to be seen. If 'true', the function prints the selector dropdown.
         *    3. columns: supply an array of column names if you want to define the 
         *       list of fields that can be used for searching: 'false' uses all displayed 
         *       fields
         *    4. sorting: you can choose to sort the list by 'column' (the order they 
         *       appear in the table), 'alpha' (alphabetical order), or 'order' which 
         *       uses the defined group/field order
         */
        $this->column_selector(false, true, false, 'column');
        ?>

        <?php $this->search_form() ?>

      </fieldset>
    <?php endif ?>
    <?php if ($filter_mode == 'sort' || $filter_mode == 'both') : ?>

      <fieldset class="widefat">
        <legend><?php _e('Sort by', 'participants-database') ?>:</legend>

        <?php
        /*
         * this function sets the fields in the sorting dropdown. It has two options:
         *    1. columns: an array of field names to show in the sorting dropdown. If 
         *       'false' shows default list of sortable fields as defined
         *    2. sorting: you can choose to sort the list by 'column' (the order they 
         *       appear in the table), 'alpha' (alphabetical order), or 'order' which 
         *       uses the defined group/field order
         */
        $this->set_sortables(false, 'column');
        ?>

        <?php $this->sort_form() ?>

      </fieldset>
    <?php endif ?>
    </form>
  </div>

</div>