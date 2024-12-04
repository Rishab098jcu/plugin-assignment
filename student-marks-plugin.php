<?php
/*
Plugin Name: Student Marks Plugin
Description: A plugin to manage student marks.
Version: 1.1
Author: Rishab Verma
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Student_Marks_Plugin {

    public function __construct() {
        add_action('init', array($this, 'create_custom_post_type'));
        add_action('add_meta_boxes', array($this, 'add_custom_meta_box'));
        add_action('save_post', array($this, 'save_custom_meta_box'), 10, 2);
        add_shortcode('student_marks', array($this, 'display_student_marks_shortcode'));
    }

    public function create_custom_post_type() {
        register_post_type('student_marks',
            array(
                'labels' => array(
                    'name' => __('Student Marks'),
                    'singular_name' => __('Student Mark')
                ),
                'public' => false,
                'has_archive' => false,
                'show_ui' => true,
                'supports' => array('title')
            )
        );
    }

    public function add_custom_meta_box() {
        add_meta_box(
            'student_marks_meta_box', 
            'Student Marks Details', 
            array($this, 'display_custom_meta_box'), 
            'student_marks', 
            'normal', 
            'high'
        );
    }

    public function display_custom_meta_box($post) {
        $marks = get_post_meta($post->ID, '_student_marks', true);
        $subject = get_post_meta($post->ID, '_subject', true);
        $registration_number = get_post_meta($post->ID, '_registration_number', true);

        wp_nonce_field(basename(__FILE__), 'student_marks_nonce');

        echo '<label for="registration_number">Registration Number:</label>';
        echo '<input type="text" name="registration_number" value="' . esc_attr($registration_number) . '" size="25" />';
        
        echo '<label for="subject">Subject:</label>';
        echo '<input type="text" name="subject" value="' . esc_attr($subject) . '" size="25" />';
        
        echo '<label for="marks">Marks:</label>';
        echo '<input type="number" name="marks" value="' . esc_attr($marks) . '" size="25" />';
    }

    public function save_custom_meta_box($post_id, $post) {
        if (!isset($_POST['student_marks_nonce']) || !wp_verify_nonce($_POST['student_marks_nonce'], basename(__FILE__))) {
            return $post_id;
        }

        $registration_number = sanitize_text_field($_POST['registration_number']);
        $subject = sanitize_text_field($_POST['subject']);
        $marks = intval($_POST['marks']);

        update_post_meta($post_id, '_registration_number', $registration_number);
        update_post_meta($post_id, '_subject', $subject);
        update_post_meta($post_id, '_student_marks', $marks);
    }

    public function display_student_marks_shortcode() {
        ob_start(); ?>
        <form method="post" id="search_student_marks">
            <input type="text" name="registration_number" placeholder="Enter Registration Number" required />
            <button type="submit">Search</button>
        </form>

        <?php
        if ($_POST && isset($_POST['registration_number'])) {
            $registration_number = sanitize_text_field($_POST['registration_number']);
            $args = array(
                'post_type' => 'student_marks',
                'meta_query' => array(
                    array(
                        'key' => '_registration_number',
                        'value' => $registration_number,
                        'compare' => '='
                    )
                )
            );

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                while ($query->have_posts()) : $query->the_post();
                    $marks = get_post_meta(get_the_ID(), '_student_marks', true);
                    $subject = get_post_meta(get_the_ID(), '_subject', true);

                    $grade = $this->get_grade($marks);
                    echo '<p>Subject: ' . esc_html($subject) . '</p>';
                    echo '<p>Marks: ' . esc_html($marks) . '</p>';
                    echo '<p>Comment: You\'ve got ' . esc_html($grade) . '</p>';
                endwhile;
            } else {
                echo '<p>No results found for Registration Number ' . esc_html($registration_number) . '</p>';
            }

            wp_reset_postdata();
        }

        return ob_get_clean();
    }

    private function get_grade($marks) {
        if ($marks >= 90) {
            return 'A';
        } elseif ($marks >= 80) {
            return 'B';
        } elseif ($marks >= 70) {
            return 'C';
        } elseif ($marks >= 50) {
            return 'D';
        } else {
            return 'F';
        }
    }
}

new Student_Marks_Plugin();
?>
