%define modname security

Summary: Issabel Security
Name: issabel-security
Version: 6.0.0
Release: 17
License: GPL
Group:   Applications/System
Source0: issabel-%{modname}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Requires(pre): issabel-framework >= 6.0.0-89
Requires(pre): issabelPBX >= 2.12.0
# EL10 no publica los nombres historicos "iptables"/"iptables-services":
# los proveen iptables-nft y iptables-nft-services (iptables.service incluido).
Requires(pre): iptables-nft
Requires(pre): iptables-nft-services
Requires: issabel-system
Requires: issabel-portknock
# sec_accessaudit incluye modules/asterisk_log/libs/LogParser_Full.class.php,
# que se distribuye en issabel-reports. Sin esta dependencia, instalar solo
# issabel-security deja la pagina Audit sin el parser del log.
Requires: issabel-reports
# Trae el DKMS de xt_geoip y construye /usr/share/xt_geoip (B9).
Requires: issabel-geoip
Requires: php-pdo
Requires: iproute
Requires: net-tools
Requires: fail2ban-server
Requires: fail2ban-sendmail
Requires: ipset-service
Requires: sqlite
Requires: certbot
Requires: python3-certbot-apache

# sec_weak_keys pulls extensions_batch/libs/paloSantoExtensionsBatch.class.php
# to perform asterisk reload
Requires: issabel-pbx >= 4.0.0-0

# commands: cut
Requires: coreutils

# /usr/share/issabel/privileged/anonymoussip recarga asterisk
# Requires: asterisk

Obsoletes: elastix-security
Obsoletes: issabel-geoip_key
Provides: issabel-geoip_key

%description
Issabel Security

%prep
%setup -n %{name}-%{version}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Issabel modules
mkdir -p    $RPM_BUILD_ROOT%{_localstatedir}/www/html/
mkdir -p    $RPM_BUILD_ROOT%{_datadir}/issabel/privileged
mkdir -p    $RPM_BUILD_ROOT/usr/src/portknock-client-sample
mv setup/usr/src/portknock-client-sample/* $RPM_BUILD_ROOT/usr/src/portknock-client-sample
rmdir setup/usr/src/portknock-client-sample
rmdir setup/usr/src

mv modules/ $RPM_BUILD_ROOT%{_localstatedir}/www/html/
mv setup/usr/share/issabel/privileged/*  $RPM_BUILD_ROOT%{_datadir}/issabel/privileged
rmdir setup/usr/share/issabel/privileged

chmod +x setup/updateDatabase
chmod +x setup/reloadIssabelconf

# Crontab for portknock authorization cleanup
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/
cp setup/etc/cron.d/issabel-portknock.cron $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/issabel-portknock.cron
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/
cp setup/etc/cron.daily/renewssl $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/
cp setup/etc/cron.daily/purgeattacks $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/jail.d/
cp setup/etc/fail2ban/jail.d/issabel.conf $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/jail.d
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/jail.d/issabel.conf

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/filter.d/
cp setup/etc/fail2ban/filter.d/asterisk-ami.conf $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/filter.d
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/filter.d/asterisk-ami.conf
cp setup/etc/fail2ban/filter.d/issabel-gui.conf $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/filter.d
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/filter.d/issabel-gui.conf

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/action.d/
cp setup/etc/fail2ban/action.d/iptables-multiport-issabel.conf $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/action.d
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/fail2ban/action.d/iptables-multiport-issabel.conf

# Startup service for portknock
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
cp setup/etc/rc.d/init.d/issabel-portknock $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
chmod 755 $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/issabel-portknock

# Portknock-related utilities
mkdir -p $RPM_BUILD_ROOT%{_bindir}/
mv setup/usr/bin/issabel-portknock* $RPM_BUILD_ROOT%{_bindir}/
chmod 755 $RPM_BUILD_ROOT%{_bindir}/issabel-portknock*
rmdir setup/usr/bin

rmdir setup/usr/share/issabel setup/usr/share setup/usr

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT%{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}/
mv setup/   $RPM_BUILD_ROOT%{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT%{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}/

%pre
mkdir -p %{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}/
touch %{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%%{VERSION}-%%{RELEASE}' %{name} > %{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
fi

%post
pathModule="%{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}"

# Run installer script to fix up ACLs and add module to Issabel menus.
issabel-menumerge $pathModule/menu.xml
pathSQLiteDB="%{_localstatedir}/www/db"
mkdir -p $pathSQLiteDB
preversion=`cat $pathModule/preversion_%{modname}.info`
rm $pathModule/preversion_%{modname}.info

if [ $1 -eq 1 ]; then #install
    # The installer database
    issabel-dbprocess "install" "$pathModule/setup/db"
    $pathModule/setup/reloadIssabelconf install
elif [ $1 -eq 2 ]; then #update
    # The update database
    $pathModule/setup/checkFields "$preversion" "$pathModule"
    issabel-dbprocess "update"  "$pathModule/setup/db" "$preversion"
    $pathModule/setup/updateDatabase "$preversion"
    $pathModule/setup/reloadIssabelconf upgrade
fi


# Create line in jail.local
if [ -f /etc/fail2ban/jail.local ]; then
grep "START issabel" /etc/fail2ban/jail.local &>/dev/null
if [ $? -eq 1 ]; then
cat >> /etc/fail2ban/jail.local <<'ISSABELJAILLOCAL'
#START issabel
[DEFAULT]
chain=F2B_INPUT
#END issabel
ISSABELJAILLOCAL
fi
else
cat >> /etc/fail2ban/jail.local <<'ISSABELJAILLOCAL'
#START issabel
[DEFAULT]
chain=F2B_INPUT
#END issabel
ISSABELJAILLOCAL
fi

# The installer script expects to be in /tmp/new_module
mkdir -p /tmp/new_module/%{modname}
cp -r $pathModule/* /tmp/new_module/%{modname}/
chown -R asterisk.asterisk /tmp/new_module/%{modname}

php /tmp/new_module/%{modname}/setup/installer.php
rm -rf /tmp/new_module

%{_datadir}/issabel/privileged/anonymoussip --conddisable

# Install issabel-portknock as a service
chkconfig --add issabel-portknock
chkconfig --level 2345 issabel-portknock on

chgrp asterisk /etc/fail2ban/jail.d
chmod g+w /etc/fail2ban/jail.d
chown asterisk.asterisk /etc/fail2ban/jail.d/issabel.conf
systemctl enable fail2ban
systemctl enable ipset

mkdir /etc/sysconfig/ipset.d
echo "create issabel_whitelist hash:ip family inet hashsize 1024 maxelem 65536" >/etc/sysconfig/ipset.d/issabel_whitelist.set
touch /etc/sysconfig/ipset.d/.saved

/usr/share/issabel/privileged/fwconfig --save_wl

if [ $1 -eq 2 ]; then #upgrade
    %{_datadir}/issabel/privileged/fwconfig --isactive >/dev/null
    if [ $? -eq 0 ]; then
        echo "Restarting Firewall"
        /usr/share/issabel/privileged/fwconfig --flush
        /usr/share/issabel/privileged/fwconfig --load
    fi
fi

/usr/bin/sqlite3 /var/www/db/attacks.db "CREATE TABLE IF NOT EXISTS attacks (source text not null, datetime datetime, done int default 0, ip text)"
chown asterisk.asterisk /var/www/db/attacks.db

/usr/share/issabel/privileged/ssl_certbot writeasteriskcert
chown asterisk.asterisk /etc/asterisk/keys -R

%postun
sed -i '/#START issabel/,/#END issabel/d' /etc/fail2ban/jail.local

%clean
rm -rf $RPM_BUILD_ROOT

%preun
pathModule="%{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}"

if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Delete Security menus"
  issabel-menuremove "%{modname}"

  echo "Dump and delete %{name} databases"
  issabel-dbprocess "delete" "$pathModule/setup/db"
fi

%files
%defattr(-, root, root)
%doc README.md docs/geoip-map.md
%{_localstatedir}/www/html/*
%{_datadir}/issabel/module_installer/*
%defattr(644, root, root)
%{_sysconfdir}/cron.d/issabel-portknock.cron
%{_sysconfdir}/fail2ban/filter.d/asterisk-ami.conf
%{_sysconfdir}/fail2ban/filter.d/issabel-gui.conf
%{_sysconfdir}/fail2ban/action.d/iptables-multiport-issabel.conf
/usr/src/portknock-client-sample/portknock-client.go
%defattr(0755, root, root)
%{_datadir}/issabel/privileged/*
%{_sysconfdir}/rc.d/init.d/issabel-portknock
%{_sysconfdir}/cron.daily/renewssl
%{_sysconfdir}/cron.daily/purgeattacks
%{_bindir}/issabel-portknock-cleanup
%{_bindir}/issabel-portknock-validate

%config
%{_sysconfdir}/fail2ban/jail.d/issabel.conf

%changelog
* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-17
- Hide the firewall apply button when there is nothing pending, show an
  "All changes applied" chip instead, and block the page with a progress
  indicator while the rules are being applied.

* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-16
- Fix reversed implode() arguments in fwconfig GeoIP rule generation. PHP 8
  throws a TypeError when a GeoIP rule has fewer than 10 countries, so applying
  the firewall aborted before loading any rule.

* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-15
- Send rawmode=yes in the firewall rules AJAX calls. Without it the Modern
  iframe layout did not dispatch the module, so the drag-and-drop reorder and
  the activation switch were applied only in the DOM and reloaded with the
  previous database state.

* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-14
- Require issabel-reports: sec_accessaudit includes LogParser_Full from the
  asterisk_log module, so installing only issabel-security left the Audit page
  unable to load the log parser.
- Require issabel-framework >= 6.0.0-89, which no longer replaces the audit log
  on upgrades.
- sec_accessaudit: handle an empty audit log without PHP warnings and show an
  explicit empty state instead of a filter with no dates.

* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-13
- Restore the firewall grid UX on top of paloSantoGrid: single action bar,
  GeoIP grid first with its own heading, traffic/target icons, switch for
  activation, edit icon and drag-and-drop (plus keyboard) reordering.
- Add "Discard changes": a filter_snapshot is saved after each successful
  apply and restored on demand.
- Harden the GeoIP preflight in fwconfig: check the userspace match, the
  kernel module and /usr/share/xt_geoip, abort instead of silently skipping
  GeoIP rules, and report a clear message. Use geoiplookup instead of the
  removed php-geoip function and avoid the external myip.issabel.org call.
- Require php-pdo and iproute, used by fwconfig.

* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-12
- Require iptables-nft and iptables-nft-services explicitly; EL10 does not
  ship the legacy "iptables"/"iptables-services" package names.
- Require issabel-geoip, which brings the xtables-addons DKMS package and the
  /usr/share/xt_geoip data used by the GeoIP firewall rules.

* Thu Oct 22 2026 Issabel Foundation <info@issabel.org> - 6.0.0-11
- Add the CSRF token to the security forms and guard the Let's Encrypt
  endpoint; state changes (fail2ban start/stop, firewall activate/deactivate,
  GeoIP block/unblock) are POST only.
- Use issabelAlert and issabel-validate: page alerts keep their success,
  warning or error variant, and invalid fields show the error under the
  control without losing the page.
- Add the compact view / edit split to whitelist, ports, jails and GeoIP key.
- Port the firewall rules page to paloSantoGrid: drop the vendored DataTables,
  chosen and toastr stack and all hardcoded colors.
- Replace amCharts in the GeoIP map with a theme SVG and a Chart.js chart; the
  reasons are documented in docs/geoip-map.md. No external IP lookup remains.
- Complete the language files for the 25 languages of the framework and add
  the missing en.lang keys.
- Fix the undefined $mode in sec_whitelist, the "Array" error message in
  sec_ports, the copied validation messages in sec_fb_admin, the unescaped
  exec in sec_geoip_key and the unescaped staging value in installcert.php.
* Mon Oct 19 2026 Issabel Foundation <info@issabel.org> - 6.0.0-10
- Let's Encrypt: use the theme alert primitives. The page used the Bootstrap 3
  "alert alert-*" classes, which Tabler does not have, so the notices showed
  without any style.

* Fri Oct 16 2026 Issabel Foundation <info@issabel.org> - 6.0.0-9
- Escape the rpm queryformat used to record the pre-version on upgrade.
  The VERSION and RELEASE macros were expanded at build time, so %pre always
  wrote the new version and issabel-dbprocess skipped every version_sql
  migration. They are now escaped so rpm expands them at run time.

* Thu Oct 15 2026 Issabel Foundation <info@issabel.org> - 6.0.0-8
- GeoIP Key: replace the removed Smarty {php} tag with a value assigned from
  PHP, so the form compiles under Smarty 4 and no longer returns a 500 error.

* Mon Oct 12 2026 Issabel Foundation <info@issabel.org> - 6.0.0-3
- sec_advanced_settings, sec_fb_admin and sec_fb_banned: drop the jQuery iButton
  plugin (and its assets); the toggles now use the theme's Bootstrap 5 switch.

* Fri Oct 09 2026 Issabel Foundation <info@issabel.org> - 6.0.0-2
- Drop the legacy cyrus jail from the fail2ban seed: Issabel 6 no longer ships
  a local mail server.
