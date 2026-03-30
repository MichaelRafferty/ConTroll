<?php
// functions related to release notes and list of release note data

global $releaseNoteList, $releaseNoteIndex, $currentRelease;

$releaseNoteList = array(
    '1.0' => 'ConTroll Version 1.0 Release Notes.md',
    '1.1' => 'ConTroll Version 1.1 Release Notes - 2025-01-27.md',
    '1.2' => 'ConTroll Version 1.2 Release Notes - 2025-03-20.md',
    '1.3' => 'ConTroll Version 1.3 Release Notes - 2025-06-01.md',
    '1.4' => 'ConTroll Version 1.4 Release Notes - 2025-07-21.md',
    '1.5' => 'ConTroll Version 1.5 Release Notes - 2025-12-01.md',
    '2.0' => 'ConTroll Version 2.0 Release Notes - 2026-03-01.md',
    '2.1' => 'ConTroll Version 2.1 Release Notes - 2026-05-01.md'
);
$currentRelease = '2.1';
$releaseNoteIndex = array();
$count = 0;
foreach ($releaseNoteList as $key => $value) {
    $releaseNoteIndex[] = $key;
}

function returnReleaseNotesLink($shown = '', $authToken = null) : string {
    global $releaseNoteList, $currentRelease;

    if ($shown == '')
        $shown = $currentRelease;

    if ($authToken == null)
        $authToken = new authToken('web');

    if ($authToken->checkAuth('admin') || $authToken->checkAuth('reg-admin'))
        return 'Release Notes: <a href="markdown.php?mdf=releaseNotes/' . $releaseNoteList[$shown] . "&releaseNoteId=$shown" . '">' . $shown . '</a>';

    return "Current ConTroll Release: $shown";
}

function releaseNotesHeaderLinks($shown) :string {
    global $releaseNoteList, $releaseNoteIndex, $currentRelease;

    if (count($releaseNoteIndex) <= 1)
        return '';

    $current = array_search($shown, $releaseNoteIndex);
    if ($current === false )
        return '';

    $linkRow = "\n";

    if ($current > 0) {
        $priorId = $releaseNoteIndex[$current - 1];
        $linkRow .= '<div class="col-sm-auto">' .
            '<a href="markdown.php?mdf=releaseNotes/' . $releaseNoteList[$priorId] . "&releaseNoteId=$priorId" . '">Prior Release: ' . $priorId . '</a>' .
            "</div>\n";
    }

    $linkRow .= '<div class="col-sm-auto">Showing Release ' . $shown . "</div>\n";

    if ($current < count($releaseNoteIndex) - 1) {
        $nextId = $releaseNoteIndex[$current + 1];
        $linkRow .= '<div class="col-sm-auto">' .
            '<a href="markdown.php?mdf=releaseNotes/' . $releaseNoteList[$nextId] . "&releaseNoteId=$nextId" . '">Next Release: ' . $nextId . '</a>' .
            "</div>\n";
    }
    return $linkRow;
}
