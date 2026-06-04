<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter4.github.io/CodeIgniter4/
 */

const DEFAULT_ID        = 0;
const DEFAULT_PARENT_ID = -1;
const DEFAULT_PER_PAGE = 25;

const TYPE_HISTORY = "history";
const META_KEY_METHOD = "method";
const META_KEY_ENABLED = "enabled";
const META_KEY_NUMBER = "number";

const USER_ID_OFFSET = 1000000000;

const METHOD_WHATSAPP = "whatsapp";
const METHOD_MESSAGE = "message";

function assets_url(string $relativePath = '', ?string $scheme = null): string
{
    return base_url("assets/". ltrim($relativePath, "\\/"), $scheme);
}