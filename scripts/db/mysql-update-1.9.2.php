<?php

$sql[] = '
ALTER TABLE extension ADD UNIQUE INDEX extension_release (namespace_module, edition, version);
';