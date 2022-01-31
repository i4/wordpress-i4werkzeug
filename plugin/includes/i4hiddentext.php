<?php
namespace i4hiddentext;

/* Der Name des Shortcodes, wie er im Editor in WordPress verwendet werden muss, um einen rohen Link einzufügen: i4subnaventry */
const SHORTCODE_NAME= 'i4hidden-text';

/* From here on, this is a port of the official hidden-text shortcode from rrze-elements (with an unclear licensing situation?).
 * The modification allows logged-in users to see the uncovered text, irrespective of the timestamp
 * The only modification is the "is_user_logged_in() || " in the conditional below
 */


    /**
     * [shortcodeHiddenText description]
     * @param  array $atts    [description]
     * @param  string $content [description]
     * @return string          [description]
     */
    function shortcodeHiddenText($atts, $content = '')
    {
        extract(shortcode_atts([
            'start' => '',
            'end' => ''
        ], $atts));

        $now = current_time('timestamp');

        $t_start = $start != '' ? strtotime($start, $now) : $now;
        $t_end = $end != '' ? strtotime($end, $now) : $now;

        if ($t_start === false || $t_end === false) {
            return do_shortcode('[notice-attention]' . __('Please use a valid date format: Y-m-d H:i:s.', 'rrze-elements') . '[/notice-attention]' . $content);
        }

        if (is_user_logged_in() || ($start != '' && $now <= $t_start) || ($end != '' && $now >= $t_end)) {
            $output = '<p>' . do_shortcode($content) . '</p>';
        } elseif (is_user_logged_in()) {
            $output = '<p style="background-color:#ff000057;" title="i4hidden-text">' . do_shortcode($content) . '</p>';
        } else {
            $output = '';
        }
        return $output;
    }

?>
