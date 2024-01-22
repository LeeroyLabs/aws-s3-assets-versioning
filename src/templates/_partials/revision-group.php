<?php
return '
    <div id="asset__revisions">
        <hr class="revision-hr">
        <h6 class="padded">'. Craft::t('aws-s3-assets-versioning', 'Admin:RecentRevisions') .'</h6>
        <ul class="padded revision-group-current" role="group">
            '. $content .'
        </ul>
    </div>
';
