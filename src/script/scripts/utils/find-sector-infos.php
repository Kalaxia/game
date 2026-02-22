<?php

// Print sectors data (and generates barycentres)

$galaxyConfiguration = $this->getContainer()->get(App\Modules\Galaxy\Galaxy\GalaxyConfiguration::class);
$galaxyConfiguration->fillSectorsData();
