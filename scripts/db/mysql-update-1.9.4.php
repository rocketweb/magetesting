<?php

$sql[] = '
DROP INDEX extension_release ON extension;
';

$sql[] = '
ALTER TABLE extension CHANGE COLUMN `namespace_module` `extension_key` VARCHAR( 255 );
';

$sql[] = '
ALTER TABLE extension ADD UNIQUE INDEX extension_release (extension_key, edition, version);
';