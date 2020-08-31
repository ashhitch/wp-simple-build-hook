<?php

/**
 * Plugin Name:     Simple Webhook Deploy
 * Plugin URI:      https://github.com/ashhitch/wpsimple-build-hook
 * Description:     A simple build hook
 * Author:          Ash Hitchcock
 * Author URI:      https://www.ashleyhitchcock.com
 * Text Domain:     simple-build-hook
 * Version:         0.0.1
 *
 * @package         WP_Build_Webhook
 */

defined('ABSPATH') or die('You do not have access to this file.');

class simpleWebHook
{


  public function __construct()
  {

    add_action('admin_menu', array($this, 'create_plugin_settings_page'));
    add_action('admin_init', array($this, 'setup_sections'));
    add_action('admin_init', array($this, 'setup_fields'));
    add_action('admin_bar_menu', array($this, 'add_to_admin_bar'), 90);
    add_action('admin_footer', array($this, 'add_js_code'));
  }


  public function plugin_settings_page_content()
  { ?>
    <div class="wrap">
      <h2><?php _e('Simple Webhook Deploy', 'simple-build-hook'); ?></h2>
      <hr>
      <h3><?php _e('Build Website', 'simple-build-hook'); ?></h3>
      <button id="simple-build-button" class="button button-primary" name="submit" type="submit">
        <?php _e('Build Site', 'simple-build-hook'); ?>
      </button>

      <div id="simple-build-status"></div>
    </div>
  <?php
  }

  public function plugin_settings_config_content()
  { ?>
    <div class="wrap">
      <h1><?php _e('Deploy Settings', 'simple-build-hook'); ?></h1>

      <?php
      if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        $this->admin_notice();
      } ?>
      <form method="POST" action="options.php">
        <?php
        settings_fields('config_webhook_fields');
        do_settings_sections('config_webhook_fields');
        submit_button();
        ?>
      </form>

    </div> <?php
          }



          public function create_plugin_settings_page()
          {
            $run_deploys = apply_filters('simple_webhook_deploy_capability', 'publish_pages');
            $adjust_settings = apply_filters('simple_webhook_adjust_settings_capability', 'publish_pages');

            if (current_user_can($run_deploys)) {
              $page_title = __('Deploy', 'simple-build-hook');
              $menu_title = __('Deploy Site', 'simple-build-hook');
              $capability = $run_deploys;
              $slug = 'deploy_webhook_fields';
              $callback = array($this, 'plugin_settings_page_content');
              $icon = 'dashicons-admin-plugins';
              $position = 100;

              add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);
            }



            if (current_user_can($adjust_settings)) {
              $sub_page_title = __('Deploy Settings', 'simple-build-hook');
              $sub_menu_title = __('Deploy Settings', 'simple-build-hook');
              $sub_capability = $adjust_settings;
              $sub_slug = 'config_webhook_fields';
              $sub_callback = array($this, 'plugin_settings_config_content');

              add_submenu_page($slug, $sub_page_title, $sub_menu_title, $sub_capability, $sub_slug, $sub_callback);
            }
          }


          public function admin_notice()
          { ?>
    <div class="notice notice-success is-dismissible">
      <p><?php _e('Your build settings have been updated!', 'simple-build-hook'); ?></p>
    </div>
  <?php
          }

          public function setup_sections()
          {


            add_settings_section('deploy_section', __('Webhook Settings', 'simple-build-hook'), array($this, 'section_callback'), 'config_webhook_fields');
          }


          public function section_callback($arguments)
          {
            switch ($arguments['id']) {
              case 'deploy_section':
                echo __('Enter build hook URL to call when build is required', 'simple-build-hook');
                break;
            }
          }



          public function setup_fields()
          {
            $fields = array(
              array(
                'uid' => 'webhook_address',
                'label' => __('Webhook Build URL', 'simple-build-hook'),
                'section' => 'deploy_section',
                'type' => 'text',
                'placeholder' => 'https://',
                'default' => '',
              )

            );
            foreach ($fields as $field) {
              add_settings_field($field['uid'], $field['label'], array($this, 'field_callback'), 'config_webhook_fields', $field['section'], $field);
              register_setting('config_webhook_fields', $field['uid']);
            }
          }


          public function field_callback($arguments)
          {

            $value = get_option($arguments['uid']);

            if (!$value) {
              $value = $arguments['default'];
            }

            switch ($arguments['type']) {
              case 'text':
              case 'password':
              case 'number':
                printf('<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value);
                break;
              case 'time':
                printf('<input name="%1$s" id="%1$s" type="time" value="%2$s" />', $arguments['uid'], $value);
                break;
              case 'textarea':
                printf('<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value);
                break;
              case 'select':
              case 'multiselect':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                  $attributes = '';
                  $options_markup = '';
                  foreach ($arguments['options'] as $key => $label) {
                    $options_markup .= sprintf('<option value="%s" %s>%s</option>', $key, selected($value[array_search($key, $value, true)], $key, false), $label);
                  }
                  if ($arguments['type'] === 'multiselect') {
                    $attributes = ' multiple="multiple" ';
                  }
                  printf('<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup);
                }
                break;
              case 'radio':
              case 'checkbox':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                  $options_markup = '';
                  $iterator = 0;
                  foreach ($arguments['options'] as $key => $label) {
                    $iterator++;
                    $options_markup .= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked(count($value) > 0 ? $value[array_search($key, $value, true)] : false, $key, false), $label, $iterator);
                  }
                  printf('<fieldset>%s</fieldset>', $options_markup);
                }
                break;
            }
          }


          public function add_to_admin_bar($admin_bar)
          {


            $run_deploys = apply_filters('simple_webhook_deploy_capability', 'publish_pages');

            if (current_user_can($run_deploys)) {
              $webhook_address = get_option('webhook_address');

              if ($webhook_address) {
                $button = array(
                  'id' => 'simple-webhook-deploy-button',
                  'title' => '<a href="#" id="simple-webhook-deploy-button"><span class="ab-icon dashicons dashicons-hammer"></span> <span class="ab-label simple-deploy-txt">' . __('Deploy', 'simple-build-hook') . '</span></a>'
                );

                $admin_bar->add_node($button);
              }
            }
          }

          public function add_js_code()
          {

  ?>
    <script type="text/javascript">
      // Simple Webhook JS
      jQuery(document).ready(function($) {

        function sendHook() {
          return $.ajax({
            type: "POST",
            url: '<?php echo (get_option('webhook_address')) ?>',
            dataType: "json"
          });
        }

        var $dButton = $("#simple-build-button");


        $dButton.on("click", function(evt) {

          evt.preventDefault();


          sendHook().done(function(vt) {
              $("#simple-build-status").html('Build Deploy requested!');
            })
            .fail(function(e) {
              console.error("error", e);
              $("#simple-build-status").html('There seems to be an error with the build!');
            });
        });

        $(document).on('click', '#simple-webhook-deploy-button', function(evt) {
          evt.preventDefault();

          var $button = $(this);
          var $label = $button.find('.simple-deploy-txt');
          $label.text('Deploying!');

          $button.attr('disabled', true);

          sendHook().done(function() {
              $button.attr('disabled', false);
            })
            .fail(function() {

              $button.attr('disabled', false);
              $label.text('FAILED!!');
              console.error("error", this);
            })
        });
      });
    </script> <?php
            }
          }

          new simpleWebHook;
              ?>