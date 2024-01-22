<?php

return '
    <div class="notifications--meta-fields">
        <div class="notification" data-type="error" style="opacity: 1; margin-bottom: 0px;">
            <div class="notification-body">
                <span class="notification-icon" data-icon="alert" aria-label="Error" role="img"></span>
                <div class="notification-main">
                    <div class="notification-message">'. Craft::t('aws-s3-assets-versioning', 'Admin:Warning') .'</div>
                </div>
            </div>
        </div>
    </div>
';