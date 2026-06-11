<?php

/**
 * Returns true if the current user is logged in.
 */
function is_logged_in(): bool
{
    return (bool) session()->get('user_uuid');
}

/**
 * Returns true if the current user is logged in and belongs to the given group.
 */
function user_in_group(string $group): bool
{
    $session = session();

    if (!$session->get('user_uuid')) {
        return false;
    }

    $groups = $session->get('groups');

    return is_array($groups) && in_array($group, $groups, true);
}
