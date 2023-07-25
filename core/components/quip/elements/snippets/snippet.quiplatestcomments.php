<?php
/**
 * Quip
 *
 * Copyright 2010-11 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of Quip, a simple commenting component for MODx Revolution.
 *
 * Quip is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Quip is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Quip; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package quip
 */
/**
 * QuipLatestComments
 *
 * Gets latest comments in a thread or by a user.
 *
 * @var modX $modx
 * @var array $scriptProperties
 * @var Quip $quip
 *
 * @name QuipLatestComments
 * @author Shaun McCormick <shaun@modx.com>
 * @package quip
 */
use Quip\Quip;
use Quip\Snippets\LatestComments;

$quip = null;
try {
    if ($modx->services->has('quip')) {
        $quip = $modx->services->get('quip');
    }
} catch (ContainerExceptionInterface $e) {
    return '';
}

if (!($quip instanceof Quip)) return '';

$snippet = $quip->loadSnippet(LatestComments::class);
if (!($snippet instanceof LatestComments)) return '';
$output = $snippet->run($scriptProperties);
return $output;