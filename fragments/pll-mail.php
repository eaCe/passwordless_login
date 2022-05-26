<?php
echo rex::getServer() . $this->route . '?' . http_build_query(['hash' => $this->hash]);
