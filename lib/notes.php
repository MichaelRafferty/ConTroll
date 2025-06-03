<?php
// notes - anything to do with the PHP side of displaying/adding reg notes so it can be used by multiple modules
// getNotes - gt the notes info for a reg id
function getNotes($regId) : array | null {
    $notes = null;

    $notesQ = <<<EOS
SELECT *
FROM regActions
WHERE regid = ? AND action = 'notes'
ORDER BY logdate DESC;
EOS;
    $notesR = dbSafeQuery($notesQ, 'i', array($regId));
    if ($notesR !== false) {
        $notes = array ();
        while ($note = $notesR->fetch_assoc()) {
            $notes[] = $note;
        }
        $notesR->free();
        if (count($notes) == 0) {
            $notes = null;
        }
    }
    return $notes;
}

function drawNotesModal($width = '80%') {
    $close = 'notes.saveNote();';
    echo <<<EOS
    <div class='modal modal-xl' id='Notes' tabindex='-2' aria-labelledby='Notes' data-bs-backdrop='static' aria-hidden='true'
         style='--bs-modal-width: $width;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary border-5 border-black'>
                    <div class='modal-title' id='NotesTitle'>Member Notes</div>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid' id='NotesBody'>
                    </div>
                </div>
                <div class='modal-footer border-5 border-black'>
                    <button type='button' id='cancel_note_button' class='btn btn-secondary' data-bs-dismiss='modal' hidden>Cancel Add Note</button>
                    <button type='button' id='close_note_button' class='btn btn-primary' onclick='$close;'>Close</button>
                </div>
            </div>
        </div>
    </div>
EOS;
}