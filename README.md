```
  ___               _          _ 
 |_ _|___ ___  __ _| |__   ___| |
  | |/ __/ __|/ _` | '_ \ / _ \ |
  | |\__ \__ \ (_| | |_) |  __/ |
 |___|___/___/\__,_|_.__/ \___|_|
```

Issabel is an open source distribution and GUI for Unified Communications systems forked from Elastix&copy;

It uses the [Asterisk©](http://www.asterisk.org/ "Asterisk Home Page") open source PBX software as its core.

Security
----

Security modules for Issabel. Firewall configuration and setup. Port knocking. Audit Logs.

Notas de diseño
----

- `docs/geoip-map.md` — por qué **GeoIP Map** dejó de usar amCharts y cómo se
  sustituyó por un SVG del tema + Chart.js (licencia, i18n, modo oscuro,
  privacidad, peso y accesibilidad).

Firewall change auditing
----

Changes that affect the firewall are recorded in `/var/log/issabel/audit.log`
and can be reviewed in **Security → Audit**. There is one line per user action,
not per database row: dragging a rule leaves a single entry, not one per rule
touched.

Format:

```
FIREWALL <user>: <event> <key=value ...> result=<ok|error> from <ip>.
```

| Module | Events |
|---|---|
| `sec_rules` | `rule.create`, `rule.update`, `rule.delete`, `rule.enable`, `rule.disable`, `rule.reorder`, `rule.move`, `rules.discard`, `rules.apply`, `rules.snapshot`, `firewall.disable` |
| `sec_geoip_map` | `country.block`, `country.unblock`, `country.none` |
| `sec_whitelist` | `whitelist.add`, `whitelist.update`, `whitelist.delete` |

Examples:

```
FIREWALL admin: rule.create id=42 traffic=INPUT protocol=TCP target=DROP src=192.0.2.0/24 dst=0.0.0.0/0 sport=ANY dport=ssh result=ok from 192.0.2.10.
FIREWALL admin: rules.apply mode=first result=ok from 192.0.2.10.
FIREWALL admin: firewall.disable result=ok from 192.0.2.10.
```

The lines are written by `modules/sec_rules/libs/issabelFwAudit.class.php`.
Every value is sanitized (no control characters, no line breaks, length
capped): `audit.log` is read by the `issabel-gui` fail2ban jail, so a free-text
rule field must not be able to inject a fake `LOGIN ...` line and get an
arbitrary IP banned. The **Audit** viewer needs no changes: the entry type is
`FIREWALL` and the user shows up in the "User" column.

Useful queries:

```sh
grep 'FIREWALL ' /var/log/issabel/audit.log
grep 'result=error' /var/log/issabel/audit.log
grep 'FIREWALL admin: rules.apply' /var/log/issabel/audit.log | tail
```

Rotation is handled by `framework` (`/etc/logrotate.d/issabelAudit.logrotate`,
monthly, 24 copies).


License
----

GPLv2 or Later

>This program is free software; you can redistribute it and/or
>modify it under the terms of the GNU General Public License
>as published by the Free Software Foundation; either version 2
>of the License, or (at your option) any later version.

>This program is distributed in the hope that it will be useful,
>but WITHOUT ANY WARRANTY; without even the implied warranty of
>MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
>GNU General Public License for more details.

>You should have received a copy of the GNU General Public License
>along with this program; if not, write to the Free Software
>Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
