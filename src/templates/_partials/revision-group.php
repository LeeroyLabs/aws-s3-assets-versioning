<?php

return '
    <button style="margin-bottom: 15px" type="button" id="context-btn" class="context-btn btn btngroup-btn-first btngroup-btn-last menubtn" aria-labelledby="context-label" role="combobox" aria-controls="menu.Garnish303966172" aria-haspopup="listbox" aria-expanded="false">
        Revision
    </button>
    <div class="revision-menu menu padded" role="listbox" id="menu.Garnish303966172" aria-labelledby="context-label" style="min-width: 36.9219px; top: 106px; max-height: 1124px; left: 300.766px; right: auto; opacity: 0; display: none;">
        <div id="asset__revisions">
            <hr class="revision-hr">
            <ul class="padded revision-group-current" role="group">
                <li class="current-revision sel" role="option" aria-selected="true" id="menu.Garnish497576472-aria-option-3">
                    <a class="sel" href="' . $e->sender->cpEditUrl .'" tabindex="-1" id="menu.Garnish497576472-option-3">
                        Current
                        <div class="smalltext">
                            Last saved on '. $e->sender->dateUpdated->format("Y-m-d H:i:s") .'
                        </div>
                    </a>
                </li>
            </ul>
            <h6 class="padded">'. Craft::t('aws-s3-assets-versioning', 'Admin:RecentRevisions') .'</h6>
            <ul class="padded revision-group-current" role="group">
                '. $content .'
            </ul>
        </div>
    </div>
    <div style="display: none;">

    </div>
';
