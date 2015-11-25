<?php
/**
 * Functions related to compliment forms.
 *
 * @since 0.0.1
 * @package BuddyPress_Compliments
 */

/**
 * Front end modal form.
 *
 * @since 0.0.1
 * @package BuddyPress_Compliments
 *
 * @param int $pid The post ID.
 * @param int $receiver_id Compliment receiver ID.
 * @param int $category_id Compliment category ID.
 */
function bp_compliments_modal_form($pid = 0, $receiver_id = 0, $category_id = 0) {
    if (!$receiver_id && bp_displayed_user_id()) {
	    $receiver_id = bp_displayed_user_id();
    }
    ?>
    <div class="comp-modal">
        <div class="comp-modal-content-wrap">
            <div class="comp-modal-title">
                <h2><?php echo sprintf( __( 'Choose Your %s Type:', 'bp-compliments' ), BP_COMP_SINGULAR_NAME ); ?></h2>
            </div>
            <div class="comp-modal-content">
                <?php
                $bp_compliment_enable_categories_value = esc_attr( get_option('bp_compliment_enable_categories'));
                $bp_compliment_enable_categories = $bp_compliment_enable_categories_value ? $bp_compliment_enable_categories_value : 'no';

                if ($bp_compliment_enable_categories == 'yes') {
                    $cat_args = array(
                        'orderby' => 'name',
                        'hide_empty' => 0,
                    );
                    $cat_terms = get_terms('compliment_category', $cat_args);
                    $output = "<ul class='comp-modal-category'>";
                    $count = 0;
                    foreach ($cat_terms as $cat_term) {
                        $count++;
                        $cat_term_id = $cat_term->term_id;
                        $cat_term_meta = get_option("taxonomy_$cat_term_id");
                        if ($cat_term_meta) {
                            $cat_term_name = $cat_term->name;
                            if ($count == 1 && !$category_id) {
                                $output .= "<li class='selected'>";
                                $category_id = $cat_term_id;
                            } elseif ($cat_term_id == $category_id) {
                                $output .= "<li class='selected'>";
                            } else {
                                $output .= "<li>";
                            }
                            $output .= "<a href='#' class='comp-modal-category-cat' data-catid='" . $cat_term_id . "'>" . $cat_term_name . "</a>";
                            $output .= "</li>";
                        }
                    }
                    $output .= "</ul>";
                    echo $output;
                }
                ?>
               <form action="" method="post">
                    <?php
                    $args = array(
                        'hide_empty' => false,
                        'orderby'  => 'id'
                    );
                    if ($category_id) {
                        $cat_meta = get_option("taxonomy_$category_id");
                        if ($cat_meta) {
                            $cat_ids = array();
                            foreach ($cat_meta as $id) {
                                $cat_ids[] = (int) $id;
                            }
                            $args['include'] = $cat_ids;
                        }
                    }
                    $terms = get_terms( 'compliment', $args );
                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                        echo '<ul class="comp-form-ul">';
                        $count = 0;
                        foreach ( $terms as $term ) {
                            $count++;
                            $t_id = $term->term_id;
                            $term_meta = get_option( "taxonomy_$t_id" );
                            ?>
                            <li>
                                <label>
                                    <input type="radio" name="term_id" value="<?php echo $term->term_id; ?>" <?php if ($count == 1) { echo 'checked="checked"'; } ?>>
                                <span>
                                    <img style="height: 20px; width: 20px; vertical-align:middle" src='<?php echo esc_attr( $term_meta['compliments_icon'] ) ? esc_attr( $term_meta['compliments_icon'] ) : ''; ?>' class='preview-upload'/>
                                    <?php echo $term->name; ?>
                                </span>
                                </label>
                            </li>
                        <?php
                        }
                        echo '</ul>';
                        $ajax_nonce = wp_create_nonce("bp-compliments-nonce");
                        ?>
                        <textarea placeholder="<?php echo __( 'Type your message here', 'bp-compliments' ); ?>" name="message" maxchar="1000"></textarea>
                        <input type="hidden" name="post_id" value="<?php echo $pid; ?>"/>
                        <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>"/>
                        <?php wp_nonce_field( 'handle_compliments_form_data','handle_compliments_nonce' ); ?>
                        <div class="bp-comp-pop-buttons">
                            <button type="submit" class="comp-submit-btn" name="comp-modal-form" value="submit"><?php echo __( 'Send', 'bp-compliments' ); ?></button>
                            <a class="bp-comp-cancel" href="#"><?php echo __( 'Cancel', 'bp-compliments' ); ?></a>
                        </div>
                        <script type="text/javascript">
                            jQuery(document).ready(function() {
                                jQuery('a.bp-comp-cancel').click(function (e) {
                                    e.preventDefault();
                                    var mod_shadow = jQuery('#bp_compliments_modal_shadow');
                                    var container = jQuery('.comp-modal');
                                    container.hide();
                                    container.replaceWith("<div class='comp-modal' style='display: none;'><div class='comp-modal-content-wrap'><div class='comp-modal-title comp-loading-icon'><div class='bp-loading-icon'></div></div></div></div>");
                                    mod_shadow.hide();
                                });
                                jQuery('a.comp-modal-category-cat').click(function (e) {
                                    e.preventDefault();
                                    var mod_shadow = jQuery('#bp_compliments_modal_shadow');
                                    var container = jQuery('.comp-modal');
                                    container.html("<div class='comp-modal-content-wrap'><div class='comp-modal-title comp-loading-icon'><div class='bp-loading-icon'></div></div></div>");
                                    var category_id = jQuery(this).data('catid');
                                    mod_shadow.show();
                                    var data = {
                                        'action': 'bp_compliments_modal_ajax',
                                        'bp_compliments_nonce': '<?php echo $ajax_nonce; ?>',
                                        'category_id': category_id
                                    };

                                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function (response) {
                                        container.replaceWith(response);
                                    });
                                });
                            });
                        </script>
                    <?php
                    } else {
                        echo __( 'No compliments found.', 'bp-compliments' );
                    }
                    ?>
                </form>
            </div>
        </div>
    </div>
<?php
}

/**
 * Compliments ajax modal form.
 *
 * @since 0.0.1
 * @package BuddyPress_Compliments
 */
function bp_compliments_modal_ajax()
{
    check_ajax_referer('bp-compliments-nonce', 'bp_compliments_nonce');

    if (isset($_POST['category_id'])) {
        $category_id = (int) strip_tags(esc_sql($_POST['category_id']));
        bp_compliments_modal_form(0, 0, $category_id);
    } else {
        bp_compliments_modal_form();
    }

    wp_die();
}

//Ajax functions
add_action('wp_ajax_bp_compliments_modal_ajax', 'bp_compliments_modal_ajax');

//Javascript
add_action('wp_footer', 'bp_compliments_modal_init');
/**
 * Initialize modal form.
 *
 * @since 0.0.1
 * @package BuddyPress_Compliments
 */
function bp_compliments_modal_init() {
    if (!bp_is_user() || !is_user_logged_in()){
        return;
    }
    $ajax_nonce = wp_create_nonce("bp-compliments-nonce");
    ?>
    <div id="bp_compliments_modal_shadow" style="display: none;"></div>
    <div class="comp-modal" style="display: none;">
        <div class="comp-modal-content-wrap">
            <div class="comp-modal-title comp-loading-icon">
                <div class="bp-loading-icon"></div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery('a.compliments-popup').click(function (e) {
                e.preventDefault();
                var mod_shadow = jQuery('#bp_compliments_modal_shadow');
                var container = jQuery('.comp-modal');
                mod_shadow.show();
                container.show();
                var data = {
                    'action': 'bp_compliments_modal_ajax',
                    'bp_compliments_nonce': '<?php echo $ajax_nonce; ?>'
                };

                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function (response) {
                    container.replaceWith(response);
                });
            });
        });
    </script>
<?php
}