<?php

return '
    <div style="display: none;">
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
';
