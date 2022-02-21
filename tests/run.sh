#! /bin/sh
#
# @author Nicolas CARPi <nico-git@deltablot.email>
# @copyright 2012 Nicolas CARPi
# @see https://www.elabftw.net Official website
# @license AGPL-3.0
# @package elabftw
#
# This script will spawn a temporary elabftw install, populate it with fake data and run the full test suite

# stop on failure
set -eu
scrutinizer=${SCRUTINIZER:-false}

# only use sudo if available e.g., alpine vs ubuntu
sudoCmd=''
if command -v sudo &> /dev/null; then
    sudoCmd='sudo'
fi

# make sure we tear down everything when script ends
cleanup() {
if (! $scrutinizer); then
    $sudoCmd cp -v config.php.dev config.php
    $sudoCmd chown 101:101 config.php
fi
}
trap cleanup EXIT

# sudo is needed because config file for docker is owned by 100:101
if (! $scrutinizer); then
    $sudoCmd cp -v config.php config.php.dev
fi
$sudoCmd cp -v tests/config-home.php config.php
$sudoCmd chmod +r config.php
# launch a fresh environment if needed
if [ ! "$(docker ps -q -f name=mysqltmp)" ]; then
    if ($scrutinizer); then
        # Don't bind mount here, files are copied. See scrutinizer.dockerfile
        # first backslash enables different delimiter than slash
        sed -i '\#volumes:#D' tests/docker-compose.yml
        sed -i '\#- \.\.:/elabftw#D' tests/docker-compose.yml
        sed -i '\#/elabftw/tests/_output/coverage#D' tests/docker-compose.yml
        # Use the freshly built elabtmp image
        sed -i 's#elabftw/elabimg:hypernext#elabtmp#' tests/docker-compose.yml
        docker build -t elabtmp -f tests/scrutinizer.dockerfile --progress plain .
    fi
    docker-compose -f tests/docker-compose.yml up -d --quiet-pull
    # give some time for containers to start
    echo -n "Waiting for containers to start..."
    while [ "`docker inspect -f {{.State.Health.Status}} elabtmp`" != "healthy" ]; do echo -n .; sleep 2; done; echo
fi
if ($scrutinizer); then
    # install and initial tests
    docker exec -it elabtmp yarn install --silent --non-interactive
    docker exec -it elabtmp yarn csslint
    docker exec -it elabtmp yarn jslint-ci
    docker exec -it elabtmp yarn buildall
    docker exec -it elabtmp composer install --no-progress -q
    docker exec -it elabtmp yarn phpcs-dry
    # extend open_basedir
    # /usr/bin/psalm, //autoload.php, /root/.cache/ are for psalm
    # /usr/bin/phpstan, /proc/cpuinfo is for phpstan, https://github.com/phpstan/phpstan/issues/4427 https://github.com/phpstan/phpstan/issues/2965
    docker exec -it elabtmp sed -i 's|^open_basedir*|&:/usr/bin/psalm://autoload\.php:/root/\.cache/:/usr/bin/phpstan:/proc/cpuinfo|' /etc/php8/php.ini
fi
# install the database
docker exec -it elabtmp bin/install start -r
if ($scrutinizer); then
    docker exec -it elabtmp yarn psalm
    docker exec -it elabtmp yarn phpstan
fi
# populate the database
docker exec -it elabtmp bin/console dev:populate tests/populate-config.yml
# run tests
# the tests are split in two parts, api and acceptance, and later unit with coverage
# this is because for some weird reason, when xdebug is enabled, there is an ssl verification error
# during the acceptance/api tests
if [ "${1:-}" != "unit" ]; then
    docker exec -it elabtmp php vendor/bin/codecept run --skip unit --skip acceptance
fi
# now install xdebug in the container so we can do code coverage
docker exec -it elabtmp bash -c "apk add --update php8-pecl-xdebug && echo 'zend_extension=xdebug.so' >> /etc/php8/php.ini && echo 'xdebug.mode=coverage' >> /etc/php8/php.ini"
# generate the coverage, results will be available in _coverage directory
docker exec -it elabtmp php vendor/bin/codecept run --skip acceptance --skip api --coverage --coverage-html --coverage-xml
if ($scrutinizer); then
    docker cp elabtmp:/elabftw/tests/_output/coverage.xml .
fi
# all tests succeeded, display a koala
cat << WALAEND


        .yyhyys/.                            -/++/:'
    -+ssydo+///ohh/                      '+yhyoo+osm'
 'ohs+////////////yh.                  .ydo//////+sysso:'
:do/////osyyyo/////om-                odo//////////////shs.
yhh+//ydo:--:+ydo///sm'             'hy//////+ooo+///////om:
-No//ms.''''''.:dy///N:             hs////shs+//+ohho////osm
hy//yd'''''''''.:No++Ns//+++++//::-+d////ds..'''''.-hd///oN+
No/:hs''''''''.-:dmdhysooooooooooosss+/:do-.''''''''.hh://do
ms//sd'''''''.:sdy+/////////////////////shs:.''''''''+N://sm
+m///ds.''''-+dy+/////////////////////////sdo-.''''''sm://sd
 od+//yh/.'.+mo/////////+//////////////////+ds-.''''+m+///do
  -yho//syyyNo///////////////////////////////m+-.-/hh+///hy'
    .+syssymy////////////////////////////////smyhhy+//+sdo'
       '..oN//////////+oooo+//////////////////Myooosyhyo.
          hy/+sydy///hyhddmmd/////ssdy+///////mdsso+:.'
          No/No/NNh:dhydddddmh:/:dy:dNm://////do
         'M+/ymNNmo/MhddddddmN///smmNmy://///+do
          ms//+++///NmmddddmmNo////++///////++m/
          od////////smNmmmmNmd/////////////++sm'
          'dy/////////ossssoo/////////////++sm:
           'yho////////oosso+///////////++ohh-
             /yho+////////////////////+oshhyy/'
             -yhyh++////:::::::///+osyhys+///sh/'
           'ohooh-'..----------::-:/:--////////yy-
          -hy/od-'''''.............''''-////////od+'
         :do/+m-''''''''.'''''''''''''''-/////////hs'
        :m+//m+''''''''..''''''''''''''''-/////////hy'
       -mo//sd'''''''''.-.'''''''''''''''.:///+s////ds
      'ds///m/''''''''''-...''''''''''''''.///:ho////N:
      /m///+N.''.'''''''...'''''''.'''''''':///+m////sd
      sh///om''...'''''''''''''''...'''''''-///:ms///+M'
      +my+/sd''..-.''''''''''''''..-.''''''.////dy+oydm
      hmNmmhm''''''''''''''''''''''..''''''.////hdhmNmN-
      -oydy/M.'''''''''''''''''''''''''''''-////do+dhs+
            do...'''''''''''''''''''''''''.:////N-
            /m/:--......'''''''''''''....-:////+N
             dy///:+++/:----------://+sys//////hs
             .Nysss+soshmosssssssso++Nhysysos+oN.
             ommNmmmdNsd+            mmmNdNmmmdy
             :hddmNmmd+:             ohddmNmmy.


WALAEND
