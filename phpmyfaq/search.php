<?php
/**
 * The fulltext search page
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('fulltext_search', 0);

// Get possible user input
$inputLanguage   = PMF_Filter::filterInput(INPUT_GET, 'langs', FILTER_SANITIZE_STRING);
$inputCategory   = PMF_Filter::filterInput(INPUT_GET, 'searchcategory', FILTER_VALIDATE_INT, '%');
$inputTag        = PMF_Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_VALIDATE_INT);
$inputSearchTerm = PMF_Filter::filterInput(INPUT_GET, 'suchbegriff', FILTER_SANITIZE_STRIPPED);
$search          = PMF_Filter::filterInput(INPUT_GET, 'search', FILTER_SANITIZE_STRIPPED);
$page            = PMF_Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);

// Search only on current language (default)
if (!is_null($inputLanguage)) {
    $allLanguages = true;
    $languages    = '&amp;langs=all';
} else {
    $allLanguages = false;
    $languages    = '';
}

if (is_null($user)) {
    $user = new PMF_User_CurrentUser();
}

$faqSearch       = new PMF_Search($db, $Language);
$faqSearchResult = new PMF_Search_Resultset($user, $faq);
$tagSearch       = false;

//
// Handle the Tagging ID
//
if (!is_null($inputTag)) {
    $tagSearch   = true;
    $tagging     = new PMF_Tags();
    $record_ids  = $tagging->getRecordsByTagId($inputTag);
    $printResult = $faq->showAllRecordsByIds($record_ids);
} else {
    $printResult = '';
}

//
// Handle the full text search stuff
//
if (!is_null($inputSearchTerm) || !is_null($search)) {
    if (!is_null($inputSearchTerm)) {
        $inputSearchTerm = $db->escapeString(strip_tags($inputSearchTerm));
    }
    if (!is_null($search)) {
        $inputSearchTerm = $db->escapeString(strip_tags($search));
    }
    
    $faqSearch->setCategory($inputCategory);
    $searchResult = $faqSearch->search($inputSearchTerm, $allLanguages);
    
    $faqSearchResult->reviewResultset($searchResult);
    
    $inputSearchTerm = stripslashes($inputSearchTerm);
    $faqSearch->logSearchTerm($inputSearchTerm);
}

// Change a little bit the $searchCategory value;
$inputCategory = ('%' == $inputCategory) ? 0 : $inputCategory;

$faqsession->userTracking('fulltext_search', $inputSearchTerm);

$mostPopularSearchData = $faqSearch->getMostPopularSearches($faqconfig->get('main.numberSearchTerms'));

if (is_numeric($inputSearchTerm) && PMF_SOLUTION_ID_START_VALUE <= $inputSearchTerm && 
    0 < $faqSearchResult->getNumberOfResults()) {
    
    // Before a redirection we must force the PHP session update for preventing data loss
    session_write_close();
    if (PMF_Configuration::getInstance()->get('main.enableRewriteRules')) {
        header('Location: '.PMF_Link::getSystemUri('/index.php') . '/solution_id_' . $inputSearchTerm . '.html');
    } else {
        header('Location: '.PMF_Link::getSystemUri('/index.php') . '/index.php?solution_id=' . $inputSearchTerm);
    }
    exit();
}

// Set base URL scheme
if (PMF_Configuration::getInstance()->get('main.enableRewriteRules')) {
    $baseUrl = sprintf("search.html?search=%s&amp;seite=%d%s&amp;searchcategory=%d",
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory);
} else {
    $baseUrl = sprintf('%s?%saction=search&amp;search=%s&amp;seite=%d%s&amp;searchcategory=%d',
        PMF_Link::getSystemRelativeUri(),
        empty($sids) ? '' : '$sids&amp;',
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory);
}

// Pagination options
$options = array(
    'baseUrl'         => $baseUrl,
    'total'           => $faqSearchResult->getNumberOfResults(),
    'perPage'         => PMF_Configuration::getInstance()->get('main.numberOfRecordsPerPage'),
    'pageParamName'   => 'seite',
    'nextPageLinkTpl' => '<a href="{LINK_URL}">' . $PMF_LANG['msgNext'] . '</a>',
    'prevPageLinkTpl' => '<a href="{LINK_URL}">' . $PMF_LANG['msgPrevious'] . '</a>',
    'layoutTpl'       => '<p align="center"><strong>{LAYOUT_CONTENT}</strong></p>');

$categoryLayout    = new PMF_Category_Layout(new PMF_Category_Tree_Helper(new PMF_Category_Tree($categoryData)));
$faqPagination     = new PMF_Pagination($options);

$faqSearchHelper = PMF_Helper_Search::getInstance();
$faqSearchHelper->setSearchterm($inputSearchTerm);
$faqSearchHelper->setPagination($faqPagination);
$faqSearchHelper->setCategoryLayout($categoryLayout);
$faqSearchHelper->setPlurals($plr);
$faqSearchHelper->setSessionId($sids);

if ('' == $printResult && !is_null($inputSearchTerm)) {
    $printResult = $faqSearchHelper->renderSearchResult($faqSearchResult, $page);
}

$tpl->processTemplate('writeContent', array(
    'msgAdvancedSearch'        => ($tagSearch ? $PMF_LANG['msgTagSearch'] : $PMF_LANG['msgAdvancedSearch']),
    'msgSearch'                => $PMF_LANG['msgSearch'],
    'searchString'             => PMF_String::htmlspecialchars($inputSearchTerm, ENT_QUOTES, 'utf-8'),
    'searchOnAllLanguages'     => $PMF_LANG['msgSearchOnAllLanguages'],
    'checkedAllLanguages'      => $allLanguages ? ' checked="checked"' : '',
    'selectCategories'         => $PMF_LANG['msgSelectCategories'],
    'allCategories'            => $PMF_LANG['msgAllCategories'],
    'printCategoryOptions'     => $categoryLayout->renderOptions(array($inputCategory)),
    'writeSendAdress'          => '?'.$sids.'action=search',
    'msgSearchWord'            => $PMF_LANG['msgSearchWord'],
    'printResult'              => $printResult,
    'openSearchLink'           => $faqSearchHelper->renderOpenSearchLink(),
    'msgMostPopularSearches'   => $PMF_LANG['msgMostPopularSearches'],
    'printMostPopularSearches' => $faqSearchHelper->renderMostPopularSearches($mostPopularSearchData)));

$tpl->includeTemplate('writeContent', 'index');
