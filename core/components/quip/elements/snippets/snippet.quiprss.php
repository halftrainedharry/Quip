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
 * Generates an RSS of latest comments for a thread or by a user.
 *
 * @var Quip $quip
 * @var modX $modx
 * @var array $scriptProperties
 *
 * @name QuipRss
 * @author Shaun McCormick <shaun@modx.com>
 * @package quip
 */
use Quip\Quip;
use Quip\Model\quipComment;
use MODX\Revolution\modUser;
use MODX\Revolution\modResource;

$quip = null;
try {
    if ($modx->services->has('quip')) {
        $quip = $modx->services->get('quip');
    }
} catch (ContainerExceptionInterface $e) {
    return '';
}

if (!($quip instanceof Quip)) return '';

/* setup default properties */
$type = $modx->getOption('type', $scriptProperties, 'all');
$tpl = $modx->getOption('tpl', $scriptProperties, 'quipRssComment');
$containerTpl = $modx->getOption('containerTpl', $scriptProperties, 'quipRssContainer');
$limit = $modx->getOption('limit', $scriptProperties, 5);
$start = $modx->getOption('start', $scriptProperties, 0);
$sortBy = $modx->getOption('sortBy', $scriptProperties, 'createdon');
$sortByAlias = $modx->getOption('sortByAlias', $scriptProperties, 'quipComment');
$sortDir = $modx->getOption('sortDir', $scriptProperties, 'DESC');

$dateFormat = $modx->getOption('dateFormat', $scriptProperties, '%b %d, %Y at %I:%M %p');
$stripTags = $modx->getOption('stripTags', $scriptProperties, true);
$bodyLimit = $modx->getOption('bodyLimit', $scriptProperties, 30);

$pagetitle = $modx->getOption('pagetitle', $scriptProperties, '');

/* build query by type */
$c = $modx->newQuery(quipComment::class);
$c->select($modx->getSelectColumns(quipComment::class, 'quipComment'));
$c->select([
    'Resource.pagetitle'
]);
$c->leftJoin(modUser::class, 'Author');
$c->leftJoin(modResource::class, 'Resource');
switch ($type) {
    case 'user':
        if (empty($scriptProperties['user'])) return '';
        if (is_numeric($scriptProperties['user'])) {
            $c->where([
                'Author.id' => $scriptProperties['user']
            ]);
        } else {
            $c->where([
                'Author.username' => $scriptProperties['user']
            ]);
        }
        break;
    case 'thread':
        if (empty($scriptProperties['thread'])) return '';
        $c->where([
            'quipComment.thread' => $scriptProperties['thread']
        ]);
        break;
    case 'family':
        if (empty($scriptProperties['family'])) return '';
        $c->where([
            'quipComment.thread:LIKE' => $scriptProperties['family']
        ]);
        break;
    case 'all':
    default:
        break;
}
$contexts = $modx->getOption('contexts', $scriptProperties, '');
if (!empty($contexts)) {
    $c->where([
        'Resource.context_key:IN' => explode(',', $contexts)
    ]);
}
$c->where([
    'quipComment.deleted' => false,
    'quipComment.approved' => true
]);
$c->sortby('`' . $sortByAlias . '`.`' . $sortBy . '`', $sortDir);
$c->limit($limit, $start);
$comments = $modx->getCollection(quipComment::class, $c);

/* iterate */
$pagePlaceholders = [];
$output = [];
$alt = false;
$commentArray = [];
/** @var quipComment $comment */
foreach ($comments as $comment) {
    $commentArray = $comment->toArray();
    $commentArray['bodyLimit'] = $bodyLimit;
    $commentArray['url'] = $comment->makeUrl(0, [], [
        'scheme' => 'full'
    ]);

    if (!empty($stripTags)) { $commentArray['body'] = strip_tags($commentArray['body']); }

    $commentArray['ago'] = $quip->getTimeAgo($commentArray['createdon']);

    $output[] = $quip->getChunk($tpl, $commentArray);
    $alt = !$alt;
}

/* set page placeholders */
$pagePlaceholders = [];
$pagePlaceholders['resource'] = $commentArray['resource'];
$pagePlaceholders['pagetitle'] = empty($pagetitle)? $commentArray['pagetitle'] : $pagetitle;
$placeholderPrefix = $modx->getOption('placeholderPrefix', $scriptProperties, 'quip.latest');
$modx->toPlaceholders($pagePlaceholders, $placeholderPrefix);

/* generate output and wrap */
$outputSeparator = $modx->getOption('outputSeparator', $scriptProperties, "\n");
$output = implode($outputSeparator, $output);

if (!empty($containerTpl)) {
    $output = $quip->getChunk($containerTpl, [
        'comments' => $output
    ]);
}

/* set to placeholder or return output */
$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, false);
if ($toPlaceholder) {
    $modx->setPlaceholder($toPlaceholder, $output);
    return '';
}
return $output;