%define modname security

Summary: Issabel Security
Name: issabel-security
Version: 6.0.0
Release: 44
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
# DNS-01 authenticators offered by sec_letsencrypt. Keeping them as hard
# Requires lets the page assume the plugin is present instead of guessing from
# the filesystem. Cloudflare, DigitalOcean and RFC 2136 are small; OVH and
# Linode are lexicon-based and pull python3-dns-lexicon, which is available in
# EL10 as its own package.
Requires: python3-certbot-dns-cloudflare
Requires: python3-certbot-dns-digitalocean
Requires: python3-certbot-dns-linode
Requires: python3-certbot-dns-ovh
Requires: python3-certbot-dns-rfc2136

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

# Validation method registry, read by the module and by ssl_certbot
mkdir -p $RPM_BUILD_ROOT%{_datadir}/issabel/letsencrypt
mv setup/usr/share/issabel/letsencrypt/providers.php $RPM_BUILD_ROOT%{_datadir}/issabel/letsencrypt/providers.php
rmdir setup/usr/share/issabel/letsencrypt

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

# Custom DNS hooks for sec_letsencrypt (root-owned allowlist)
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/letsencrypt/hooks
cp setup/etc/letsencrypt/hooks/hooks.json $RPM_BUILD_ROOT%{_sysconfdir}/letsencrypt/hooks/hooks.json
cp setup/etc/letsencrypt/hooks/README $RPM_BUILD_ROOT%{_sysconfdir}/letsencrypt/hooks/README
cp setup/etc/letsencrypt/hooks/example.sh $RPM_BUILD_ROOT%{_sysconfdir}/letsencrypt/hooks/example.sh
chmod 755 $RPM_BUILD_ROOT%{_sysconfdir}/letsencrypt/hooks
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/letsencrypt/hooks/hooks.json
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/letsencrypt/hooks/README
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/letsencrypt/hooks/example.sh

# Startup service for portknock
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
cp setup/etc/rc.d/init.d/issabel-portknock $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/
chmod 755 $RPM_BUILD_ROOT%{_sysconfdir}/rc.d/init.d/issabel-portknock

# Portknock-related utilities
mkdir -p $RPM_BUILD_ROOT%{_bindir}/
mv setup/usr/bin/issabel-portknock* $RPM_BUILD_ROOT%{_bindir}/
chmod 755 $RPM_BUILD_ROOT%{_bindir}/issabel-portknock*
mv setup/usr/bin/issabel-firewall $RPM_BUILD_ROOT%{_bindir}/
chmod 755 $RPM_BUILD_ROOT%{_bindir}/issabel-firewall
rmdir setup/usr/bin

rmdir setup/usr/share/issabel setup/usr/share setup/usr

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT%{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}/
mv setup/   $RPM_BUILD_ROOT%{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT%{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}/

# Safe Mode: drop-in de iptables.service (sin unidad nueva)
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/systemd/system/iptables.service.d
cp setup/etc/systemd/system/iptables.service.d/issabel-safemode.conf \
   $RPM_BUILD_ROOT%{_sysconfdir}/systemd/system/iptables.service.d/
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/systemd/system/iptables.service.d/issabel-safemode.conf

# Reglas propias del operador
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/issabel/firewall.d
cp setup/etc/issabel/firewall.d/README $RPM_BUILD_ROOT%{_sysconfdir}/issabel/firewall.d/
cp setup/etc/issabel/firewall.d/10-custom.rules.sample $RPM_BUILD_ROOT%{_sysconfdir}/issabel/firewall.d/
chmod 644 $RPM_BUILD_ROOT%{_sysconfdir}/issabel/firewall.d/*

# Chequeo periodico de integridad
mkdir -p $RPM_BUILD_ROOT%{_unitdir}
cp setup/etc/systemd/system/issabel-fwcheck.service $RPM_BUILD_ROOT%{_unitdir}/
cp setup/etc/systemd/system/issabel-fwcheck.timer $RPM_BUILD_ROOT%{_unitdir}/
cp setup/etc/systemd/system/issabel-fwddns.service $RPM_BUILD_ROOT%{_unitdir}/
cp setup/etc/systemd/system/issabel-fwddns.timer $RPM_BUILD_ROOT%{_unitdir}/
cp setup/etc/systemd/system/issabel-fwresponsive.service $RPM_BUILD_ROOT%{_unitdir}/
chmod 644 $RPM_BUILD_ROOT%{_unitdir}/issabel-fwcheck.service $RPM_BUILD_ROOT%{_unitdir}/issabel-fwcheck.timer
chmod 644 $RPM_BUILD_ROOT%{_unitdir}/issabel-fwddns.service $RPM_BUILD_ROOT%{_unitdir}/issabel-fwddns.timer
chmod 644 $RPM_BUILD_ROOT%{_unitdir}/issabel-fwresponsive.service

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

# 6.0.0-28..32 declared `deactivated` both in the install schema and in the
# 27->28 version_sql, so the ALTER always failed on a clean install and left
# /var/spool/issabel-mysqldbscripts/iptables_install.sql behind: firstboot
# warned about it on every boot even though the database was complete. Drop
# the stale script only when every table and the column are actually there.
if [ -f /var/spool/issabel-mysqldbscripts/iptables_install.sql ] && \
   [ -f /var/www/db/iptables.db ]; then
    tables=`/usr/bin/sqlite3 /var/www/db/iptables.db "SELECT count(*) FROM sqlite_master WHERE type='table' AND name IN ('tmp_execute','whitelist','portknock_eth','portknock_user_auth','portknock_user_current_rule');" 2>/dev/null`
    hascol=`/usr/bin/sqlite3 /var/www/db/iptables.db "PRAGMA table_info(tmp_execute);" 2>/dev/null | grep -c '|deactivated|'`
    if [ "$tables" = "5" ] && [ "$hascol" = "1" ]; then
        echo "Removing stale iptables script from the firstboot spool"
        rm -f /var/spool/issabel-mysqldbscripts/iptables_install.sql
    fi
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
systemctl daemon-reload >/dev/null 2>&1 || true
systemctl enable issabel-fwcheck.timer >/dev/null 2>&1 || true
systemctl enable issabel-fwddns.timer >/dev/null 2>&1 || true

mkdir -p /etc/sysconfig/ipset.d
echo "create issabel_whitelist hash:net family inet hashsize 1024 maxelem 65536" >/etc/sysconfig/ipset.d/issabel_whitelist.set
echo "create issabel_whitelist6 hash:net family inet6 hashsize 1024 maxelem 65536" >/etc/sysconfig/ipset.d/issabel_whitelist6.set
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
systemctl daemon-reload >/dev/null 2>&1 || true

%clean
rm -rf $RPM_BUILD_ROOT

%preun
pathModule="%{_datadir}/issabel/module_installer/%{name}-%{version}-%{release}"

if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Delete Security menus"
  issabel-menuremove "%{modname}"

  echo "Dump and delete %{name} databases"
  issabel-dbprocess "delete" "$pathModule/setup/db"

  systemctl disable issabel-fwcheck.timer >/dev/null 2>&1 || true
  systemctl disable issabel-fwddns.timer >/dev/null 2>&1 || true
  systemctl disable --now issabel-fwresponsive >/dev/null 2>&1 || true
  systemctl daemon-reload >/dev/null 2>&1 || true
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
%dir %attr(0755, root, root) %{_sysconfdir}/systemd/system/iptables.service.d
%config(noreplace) %attr(0644, root, root) %{_sysconfdir}/systemd/system/iptables.service.d/issabel-safemode.conf
%dir %attr(0755, root, root) %{_sysconfdir}/issabel/firewall.d
%config(noreplace) %attr(0644, root, root) %{_sysconfdir}/issabel/firewall.d/README
%config(noreplace) %attr(0644, root, root) %{_sysconfdir}/issabel/firewall.d/10-custom.rules.sample
%attr(0644, root, root) %{_unitdir}/issabel-fwcheck.service
%attr(0644, root, root) %{_unitdir}/issabel-fwcheck.timer
%attr(0644, root, root) %{_unitdir}/issabel-fwddns.service
%attr(0644, root, root) %{_unitdir}/issabel-fwddns.timer
%attr(0644, root, root) %{_unitdir}/issabel-fwresponsive.service
%{_datadir}/issabel/letsencrypt/providers.php
%dir %attr(0755, root, root) %{_sysconfdir}/letsencrypt/hooks
%config(noreplace) %attr(0644, root, root) %{_sysconfdir}/letsencrypt/hooks/hooks.json
%config(noreplace) %attr(0644, root, root) %{_sysconfdir}/letsencrypt/hooks/example.sh
%attr(0644, root, root) %{_sysconfdir}/letsencrypt/hooks/README
/usr/src/portknock-client-sample/portknock-client.go
%defattr(0755, root, root)
%{_datadir}/issabel/privileged/*
%{_sysconfdir}/rc.d/init.d/issabel-portknock
%{_sysconfdir}/cron.daily/renewssl
%{_sysconfdir}/cron.daily/purgeattacks
%{_bindir}/issabel-portknock-cleanup
%{_bindir}/issabel-portknock-validate
%{_bindir}/issabel-firewall

%config
%{_sysconfdir}/fail2ban/jail.d/issabel.conf

%changelog
* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-44
- Add the simple Responsive Firewall (PJSIP UDP): toggle default off,
  issabel_responsive{,6} ipset with timeout, ISSABEL_RESPONSIVE chain with the
  set ACCEPT and a hashlimit probation, AMI user issabel-firewall and the
  fwresponsive daemon. `fwconfig --responsive on|off|flush|status|apply`
  manages it atomically; the sec_responsive page and `issabel-firewall
  responsive` expose it. It only opens SIP and RTP, never SSH/HTTPS.

* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-43
- Add the Discovery module (sec_discovery) and the fwdiscover helper: they
  read the generated PJSIP/chan_sip/IAX/RTP configuration and suggest ports
  and static peers. The page can add a port to the catalog, add a peer to the
  whitelist, or create a disabled ACCEPT rule; every action is POST + CSRF
  and audited.

* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-42
- Whitelist entries can be a hostname/DDNS name. The form resolves it at save
  time and stores the effective IP in ip_address, keeping the hostname for
  display. fwconfig --resolve_wl re-resolves every hostname and updates the
  ipset when the address changes; issabel-fwddns.timer runs it every 5
  minutes and whitelist.resolve is audited.

* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-41
- Add the Let's Encrypt port filter (`issabel_leports`, bitmap:port). The
  firewall accepts the ports in the set before the final REJECT, and
  `fwconfig --le-open/--le-close` manage it.
- ssl_certbot opens port 80 only around HTTP-01 issuance, dry-run and
  renewal, closes it in a finally block, cleans a stale set at the start of
  renewcertificate and audits letsencrypt.http01.open/close.
- `fwconfig --load` closes a stale set unless a certbot process is running.

* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-40
- Add `fwconfig --check`: JSON integrity report for the INPUT order
  (whitelist, F2B_INPUT, ISSABEL_PRE, F2B_RBL, ISSABEL_INPUT), missing sets,
  fail2ban, packetbl NFQUEUE placement and new interfaces. It writes an audit
  line when something is wrong.
- Add the `issabel-fwcheck.timer` (every 15 minutes) and show the integrity
  problems as an error banner on the rules page.

* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-39
- Add custom firewall rules in /etc/issabel/firewall.d (*.rules for IPv4,
  *.rules6 for IPv6). fwconfig validates them with iptables-restore --test,
  requires root:root and non-world-writable files, rejects symlinks, and
  applies them with --noflush before the normal rules so they are not placed
  behind the final REJECT.

* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-38
- Add the `issabel-firewall` CLI: status (plain/JSON), enable/disable
  (respecting the lock unless --force), apply, list whitelist/blocklist,
  trust/untrust, blocklist add/del, check and safemode status/apply.
  It writes its own audit lines and keeps the database and ipset in sync.

* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-37
- Move the fail2ban `jails` table to setup/db and seed one row per real jail
  (15) instead of four groups. The version_sql expands the old groups and
  drops the `apache` group row.
- Add `findtime` to the jails table and the Admin page (seconds).
- F2BUpdateJails no longer truncates jail.d/issabel.conf when the table is
  empty or missing; it updates only the managed keys of the sections that
  already exist in the file, preserving the rest.
- The install seed no longer opens SSH/HTTPS/SIP/RTP/IAX/MGCP to the world:
  those rules are created inactive. The firstboot profile whitelists the
  management IP, so SSH/HTTPS keep working from it while everything else is
  denied by default.

* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-36
- Add Safe Mode without a new service: a drop-in of iptables.service runs
  fwboot-pre, which detects two boots within 5 minutes, writes a minimal
  IPv4 ruleset (SSH/HTTPS only) and schedules fwrestore with a transient
  systemd unit. IPv6 keeps its full ruleset.
- Add the firewall lock: fwconfig --lock on|off, --islocked and --force.
  The rules page hides the deactivate button and rejects the POST when the
  lock is present; the console can still override it with --force.
- Add the Safe Mode banner and "Apply now" action to the rules page.

* Wed Nov 18 2026 Issabel Foundation <info@issabel.org> - 6.0.0-35
- Add the Block List module (sec_blocklist), a mirror of the White List:
  persistent ipset blocklist for IPv4/IPv6, `fwconfig --add_bl/--remove_bl/
  --save_bl`, its own chain ISSABEL_BLOCK and audit events.
- Add the priority field to firewall rules. An ACCEPT rule can be Normal
  (default) or High; High rules are emitted in ISSABEL_HIGH/
  ISSABEL_HIGH_FORWARD and evaluated after fail2ban bans and before packetbl,
  GeoIP and Block List. Only INPUT/FORWARD ACCEPT rules can be High.
- Reorder the INPUT/FORWARD chains: whitelist, F2B_INPUT (bans), ISSABEL_PRE
  (High + Block List), F2B_RBL (packetbl) and ISSABEL_INPUT/FORWARD.
- Keep F2B_INPUT for fail2ban bans only; packetbl moves to F2B_RBL so a High
  rule cannot bypass fail2ban.
- Fix B13: the fail2ban actionstop now removes the real `INPUT -j F2B_INPUT`
  jump instead of the nonexistent `INPUT_F2B`.
* Fri Nov 13 2026 Issabel Foundation <info@issabel.org> - 6.0.0-34
- Add IPv6 filtering to the firewall engine. `filter` gets a `family` column
  (`inet` by default, so every existing rule and the whole IPv4 behaviour stay
  untouched) and fwconfig loads the `inet6` rules into ip6tables, persisting
  them in /etc/sysconfig/ip6tables.
- IPv6 is opt-in: `tmp_execute.ipv6_enabled` defaults to 0 and only then does
  fwconfig touch ip6tables. It is switched on from the firewall rules page and
  applied with the usual Apply button, so no upgrade starts filtering IPv6 on
  its own.
- Seed loopback, ICMPv6 and established/related for IPv6, and always emit the
  IPv6 REJECT at the end of the chain. NDP/SLAAC survive the first load and
  IPv6 can never be left fail-open by deleting that rule.
- One rules grid, mixed families: a Family column plus an All/IPv4/IPv6 filter,
  and a family selector in the rule form. `both` is not exposed yet, so a rule
  is always evaluated by exactly one family and no matcher is silently dropped.
- A new rule is now created before the catch-all REJECT of its family and
  traffic instead of at the end of the list. Before this, a new IPv4 rule was
  appended below the default REJECT and never matched until it was reordered.
- Give the whitelist an IPv6 set (`issabel_whitelist6`) and hook both sets at
  the top of INPUT. Entries are routed to the set of their family.
- Move both whitelist sets from `hash:ip` to `hash:net`: the module has always
  accepted networks in the form, but `hash:ip` rejects anything with a prefix,
  so adding `192.168.1.0/24` failed after the row was already stored. The sets
  are recreated on the next load and repopulated from the database.
- Validate whitelist entries with IPv4/IPv6 (prefix up to 128) instead of the
  IPv4-only regex, reject the `0.0.0.0` and `::` wildcards, and roll back the
  database row when the firewall refuses the entry.
- Finish IPv6 banning in fail2ban. The custom banaction already runs once per
  family on fail2ban 1.1 (the included iptables.conf brings the
  `[Init?family=inet6]` section), so ip6tables gets its own `F2B_INPUT` and
  `f2b-*` chains and an IPv6 offender is rejected with
  `icmp6-port-unreachable`; verified against fail2ban 1.1.0-6.el10_0. The
  action now documents the mechanism, because writing the IPv6 commands by
  hand makes them run in the IPv4 pass too and duplicates every rule.
- Read the banned addresses with `preg_split("/:/", ..., 2)`: splitting on
  every colon truncated IPv6 addresses in the GUI list (`2001:db8::1` showed as
  `2001`) and made them impossible to unban.
- Look up the country with `geoiplookup6` for IPv6 bans, and default it to `ZZ`
  when the lookup fails, so the attacks table no longer stores an empty source.
- Report by family: the rules page shows whether IPv6 is active or still
  pending and offers Apply when IPv6 is enabled but its chains are not loaded
  (failed apply, manual flush, boot without ip6tables.service); the rejected-IP
  list classifies IPv6 addresses by range instead of a generic "IPv6 address";
  and the fail2ban blocked-by-country report walks both tables, stripping the
  /32 and /128 suffix that iptables prints (which also fixes the IPv4 report,
  that always fell back to `ZZ`).
- GeoIP for IPv6: the GEOIP rules are emitted to ip6tables in their own
  `ISSABEL_*_GEOIP` chains, and the match is checked per family (the `.iv4` and
  `.iv6` files of /usr/share/xt_geoip are different artefacts, so having one
  does not enable the other). The rule form accepts IPv6 for a GeoIP rule and
  the page warns when such rules exist without .iv6 data.
- Blocking or whitelisting a country from the GeoIP map now covers both
  families when IPv6 filtering is enabled, and fails with a clear message when
  the .iv6 data is missing instead of leaving an IPv4-only rule that would be a
  silent fail-open for IPv6. The blocked and whitelisted lists merge both
  tables.
- The `issabel-geoip` updater now fails when the .iv6 files are missing too, not
  only the .iv4 ones (separate package).

* Wed Nov 11 2026 Issabel Foundation <info@issabel.org> - 6.0.0-33
- Keep `deactivated` out of the iptables install schema. A clean install runs
  the schema and then every version_sql on top of it, so declaring the column
  in both places made the 6.0.0-28 ALTER fail with "duplicate column name":
  the whole iptables script was deferred to /var/spool/issabel-mysqldbscripts
  and firstboot warned about it on every boot. Upgrades from 6.0.0-27 still get
  the column from the version_sql.
- Drop that stale spool script from %post when the iptables database already
  has every table and the column, so boxes installed with 28..32 stop warning
  after upgrading instead of waiting for a manual rm.

* Wed Nov 11 2026 Issabel Foundation <info@issabel.org> - 6.0.0-32
- Add custom DNS-01 hooks to the HTTPS Certificate module for providers without
  a certbot plugin or for internal DNS servers. The hook is selected by id from
  a root-owned manifest in /etc/letsencrypt/hooks; the privileged helper
  validates ownership, permissions, symlinks and path before handing the script
  to certbot's manual plugin, so the web user can never choose a command.

* Wed Nov 11 2026 Issabel Foundation <info@issabel.org> - 6.0.0-31
- Add DigitalOcean, OVH and Linode DNS-01 authenticators to the HTTPS
  Certificate module. Like Cloudflare, they need no inbound port and allow
  wildcard names, and their credentials are validated field by field and stored
  root-only in /etc/letsencrypt/credentials.

* Wed Nov 11 2026 Issabel Foundation <info@issabel.org> - 6.0.0-30
- Add DNS-01 validation to the HTTPS Certificate module, with Cloudflare and
  RFC 2136 authenticators. DNS-01 needs no inbound port and allows wildcard
  names, so it works behind NAT and with port 80 closed.
- Move the method definition to a root-owned registry shared by the GUI and
  the privileged helper, send credentials to the helper over stdin (never in
  argv), validate every field before writing the certbot INI, and run certbot
  with an argv array instead of a shell string.
- Add a Test Validation button that runs certbot with --dry-run, and install
  DNS-01 certificates ourselves (certonly plus ssl.conf rewrite, configtest
  and rollback), which removes the Apache installer from that path.
- Renewal now reloads httpd and rebuilds the Asterisk PEM through a certbot
  deploy hook instead of only doing it after a manual run.

* Wed Nov 11 2026 Issabel Foundation <info@issabel.org> - 6.0.0-29
- Log firewall activation as its own audit event. Turning the firewall on now
  writes firewall.enable, mirroring firewall.disable, while applying changes
  over an already running firewall stays as rules.apply.

* Wed Nov 11 2026 Issabel Foundation <info@issabel.org> - 6.0.0-28
- Hide the "Deactivate firewall" button while the firewall is not running and
  ignore deactivation requests in that state, so the rules page no longer
  offers an action that does nothing.
- Distinguish a firewall that was explicitly deactivated from one that was
  never activated, using the new tmp_execute.deactivated flag, so the rules
  page notice matches what the administrator did.

* Fri Nov 06 2026 Issabel Foundation <info@issabel.org> - 6.0.0-27
- Rebuild the new firewall rule form as a compact six-column grid: the four
  stacked cards become two, and the protocol-dependent fields move to their
  own card that appears as a block, so changing the protocol no longer
  reflows the fields above it. The GeoIP multiselects get a fixed size and
  the country list a filter box. The form now fits without scrolling.

* Tue Oct 27 2026 Issabel Foundation <info@issabel.org> - 6.0.0-26
- Keep GeoIP rules in their own rule_order range. paloSantoRules::reorder(),
  which runs after every delete, renumbered them from 1, mixing them with the
  normal rules until fixGEOIPOrder() repaired them on the next read, and
  saveRule() stored the first GeoIP rule at order 1 because MAX() is NULL on
  an empty family. Both now use the new paloSantoRules::GEOIP_ORDER_BASE
  (100000), the value the grid and drag-and-drop reordering already used.
  Existing databases are still repaired by fixGEOIPOrder().

* Tue Oct 27 2026 Issabel Foundation <info@issabel.org> - 6.0.0-25
- Enrich the firewall rule audit entries so a line identifies the rule it
  refers to. Each event now carries the rule's identity card (traffic,
  protocol and its detail, target, activated, interfaces, source and
  destination) in addition to the filter id, and the position shown in the
  grid as order=, because the two numbers differ after an upgrade or a
  reorder. rule.enable/rule.disable and rule.move no longer log a bare id, and
  rule.delete writes one line per deleted rule with a count= of the rules
  requested, since the row is gone from the database afterwards and the log is
  the only record of what was removed.

* Sat Oct 24 2026 Issabel Foundation <info@issabel.org> - 6.0.0-24
- Include PJSIP extensions in the Weak Keys report. PJSIP extension secrets
  are stored in the sip table keyed by extension, so the listing, the total
  count and the Change Secret action now cover tech='pjsip' along with SIP
  and IAX2, and paginate over the whole set. The update now compares the id
  as text, so MariaDB strict mode no longer fails on non-numeric ids such as
  the trunk rows stored in the same table.

* Sat Oct 24 2026 Issabel Foundation <info@issabel.org> - 6.0.0-23
- Log firewall changes in /var/log/issabel/audit.log: rule create, update,
  delete, enable/disable, reorder, move, discard and apply, firewall
  deactivation, GeoIP country block/unblock and whitelist add/update/delete.
  Every field is sanitized before being written, so a free-text rule field
  cannot inject a fake login line that fail2ban would act on.

* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-21
- Fix mistranslated status labels in the advanced security settings
  language files: Spanish "Discapacitado" for Disabled, Bulgarian and
  Persian Disabled/Enabled, and "Dismiss" translated as firing an
  employee in German, Russian, Ukrainian and Chinese.

* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-20
- Reload the firewall rules page with a GET request after AJAX actions,
  so toggles and reordering still refresh after the POST used by Apply.

* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-19
- Replace the firewall traffic GIFs with Tabler direction icons and color
  input/output/forward consistently with the theme palette.

* Fri Oct 23 2026 Issabel Foundation <info@issabel.org> - 6.0.0-18
- Allow reordering firewall rules by dragging any row, not only the grip,
  and auto-scroll the page while dragging near the viewport edges.

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
