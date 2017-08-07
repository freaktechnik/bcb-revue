<?php
/*
Plugin Name: BCB Revue
Description: Adds a BCB Revue entrytype.
Version: 1.0.0
Author: Martin Giger
Author URI: https://humanoids.be
License: MIT
Text-Domain: bcb-revue
*/

/**
 * @var string
 */
define("BCBR_TEXT_DOMAIN", "bcb-revue");

class BCBRevuePlugin {
    /**
     * @var string
     */
    const POST_TYPE = 'bcb-revue';
    /**
     * @var string
     */
    const REVUE_FIELD = 'bcbr_revue';
    /**
     * @var string
     */
    const NONCE_FIELD = 'bcbr_revue_box';
    /**
     * @var string
     */
    const NONCE_NAME = 'bcbcr_revue_boc_nonce';

    public function __construct() {
        $this->registerHooks();
    }

    private function registerHooks() {
        add_action('init', [$this, 'onInit']);
        if(is_admin()) {
            add_action('load-post.php', [$this, 'onLoad']);
            add_action('load-post-new.php', [$this, 'onLoad']);
            add_action('admin_enqueue_scripts', [$this, 'onEnqueue']);
        }
    }

    public function onInit() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('BCB Revue Ausgaben', BCBR_TEXT_DOMAIN),
                'singular_name' => __('BCB Revue Asugabe', BCBR_TEXT_DOMAIN)
            ],
            'public' => true,
            'show_in_nav_menus' => false,
            'has_archive' => true,
            'supports' => [
                'title'
            ],
            'rewrite' => [
                'slug' => 'bcb-revue'
            ]
        ]);
    }

    public function onLoad() {
        add_action('add_meta_boxes', [$this, 'onBoxes']);
        add_action('save_post', [$this, 'onSave']);
    }

    public function onBoxes(string $post_type) {
        if(self::POST_TYPE === $post_type) {
            add_meta_box(
                self::REVUE_FIELD,
                __('BCB Revue', BC_TEXT_DOMAIN),
                [$this, 'renderBox'],
                self::POST_TYPE
            );
        }
    }

    public function renderBox($post) {
        wp_nonce_field(self::NONCE_FIELD, self::NONCE_NAME);

        $iframe = esc_url(get_upload_iframe_src('pdf', $post->ID));

        $content = get_post_meta($post->ID, self::REVUE_FIELD, true);
        $contentSrc = wp_get_attachment_image_src($content, 'full');
        $hasImage = is_array($contentSrc);
        ?>
        <div class="bcbr-prev-container">
            <?php if($hasImage) { ?>
            <img src="<?php echo esc_url($contentSrc[0]) ?>" alt="BCB Revue Deckblatt" style="max-width:100%;">
            <?php } ?>
        </div>
        <p class="hide-if-no-js">
            <a class="bcbr-upload <?php if($hasImage) { echo 'hidden'; } ?>" href="<?php echo $iframe ?>">
                <?php _e('Revue hochladen', BCBR_TEXT_DOMAIN) ?>
            </a>
            <a class="bcbr-delete <?php if(!$hasImage) { echo 'hidden'; } ?>" href="#">
                <?php _e('Revue entfernen', BCBR_TEXT_DOMAIN) ?>
            </a>
        </p>
        <input class="bcbr-revue-id" name="bcbr_revue_id" type="hidden" value="<?php echo esc_attr($content); ?>" />
        <?php
        wp_enqueue_script('bcbr-upload');
    }

    public function onSave($post_id) {
        if(!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_FIELD)) {
            return $post_id;
        }

        if(get_post_type($post_id) == self::POST_TYPE) {
            $content = sanitize_text_field($_POST['bcbr_revue_id']);
            update_post_meta($post_id, self::REVUE_FIELD, $content);
        }
    }

    public function onEnqueue() {
        wp_enqueue_media();
        wp_register_script('bcbr-upload', plugin_dir_url(__FILE__).'admin/js/revue.js', [], "1.0.0", true);
    }
}

new BCBRevuePlugin();
