<?php

/**
 * Generate image initials
 *
 * @param string $title Title to generate initials from.
 * @param string $background Background color.
 * @param string $color Text color.
 * @param integer $size Size.
 *
 * @return string
 * @since   1.0.0
 * @version 1.0.0
 *
 * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
 */
function img_initials(string $title, string $background = '7dac6e', string $color = 'fff', int $size = 48): string
{
    return '<img src="' . base_url('media/avatar') . '?name=' . urlencode(trim($title)) . '&background=' . $background . '&color=' . $color . '&rounded=true&size=' . $size . '" alt="user" class="avatar-img rounded-circle">';
}