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
and can be reviewed in **Security → Audit**. Normally there is one line per
user action, not per database row: dragging a rule leaves a single entry, not
one per rule touched. `rule.delete` is the exception and writes one line per
deleted rule, because the row no longer exists afterwards (see below).

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
FIREWALL admin: rule.create id=42 order=20 traffic=INPUT protocol=TCP target=DROP activated=on src=192.0.2.0/24 dst=0.0.0.0/0 sport=ANY dport=ssh result=ok from 192.0.2.10.
FIREWALL admin: rule.enable id=19 order=20 traffic=INPUT protocol=TCP target=ACCEPT activated=on src=0.0.0.0/0 dst=0.0.0.0/0 sport=ANY dport=SSH result=ok from 192.168.11.138.
FIREWALL admin: rule.move id=19 order=21 traffic=INPUT protocol=TCP target=ACCEPT activated=on src=0.0.0.0/0 dst=0.0.0.0/0 sport=ANY dport=SSH dir=down result=ok from 192.0.2.10.
FIREWALL admin: rule.delete id=19 order=20 traffic=INPUT protocol=TCP target=ACCEPT activated=on src=0.0.0.0/0 dst=0.0.0.0/0 sport=ANY dport=SSH count=1 result=ok from 192.0.2.10.
FIREWALL admin: rules.apply mode=first result=ok from 192.0.2.10.
FIREWALL admin: firewall.disable result=ok from 192.0.2.10.
```

### What a rule entry carries

Every per-rule event carries the same "identity card", read from the database
at the time of the action:

| Field | Meaning |
|---|---|
| `id` | primary key of the `filter` table |
| `order` | position shown in the grid (`rule_order`) |
| `traffic` | `INPUT`, `OUTPUT` or `FORWARD` |
| `protocol` | `ALL`, `TCP`, `UDP`, `ICMP`, `IP`, `STATE` or `GEOIP` |
| `target` | `ACCEPT`, `DROP` or `REJECT` |
| `activated` | `on` or `off` — whether the rule was effective |
| `iface_in` / `iface_out` | omitted when empty |
| `src` / `dst` | source and destination as stored (`ANY` if empty) |
| protocol detail | `sport`/`dport` (port names), `icmp_type`, `ip_proto`, `state`, or `countries`/`continents` |

Ports are logged by name, not by their `port` table id, so `dport=SSH` is what
the grid shows. Long lists (GeoIP countries) are capped at 10 entries plus
`+N`. The whole line is capped at 400 characters by the writer.

### One line per action, except `rule.delete`

Every action leaves a single entry: dragging a rule is one `rule.reorder` line
with `geoip` and `count`, not one per rule moved, because in a reorder the
order is a property of the whole list.

`rule.delete` is the exception: it writes **one line per deleted rule**, each
with its own identity card and a `count=` with the number of rules requested
in the action. A deleted rule is gone from the database, so the log is the only
record of what was removed; a summary line listing bare ids would not say which
rule it was. Failed deletions are logged the same way, with `result=error` and
the helper's message in `msg`.

### `id` and `order` are not the same number

Every per-rule event carries two identifiers:

- `id` — the primary key of the `filter` table. Stable for the life of the
  rule; this is what the edit page and the checkboxes use.
- `order` — the position the rule has in the grid, i.e. `rule_order`. Rules
  with `protocol=GEOIP` are stored from 100000 and logged minus that offset,
  so the value matches what the grid shows.

They differ in normal operation, so an audit line saying `id=19` can well be
the rule displayed in position 20:

- `security`'s `version_sql` upgrades insert rules in the middle of the list
  (the WEBRTC/XMPP ones, for instance) and shift `rule_order` for everything
  below, without renumbering `id`.
- Reordering, moving or deleting a rule renumbers `rule_order`; `id` stays.

`rule.reorder` is the only event that does not identify individual rules: it
records `geoip` and `count`, and leaves a single entry for the whole drag.

Note that lines written before this distinction was logged cannot be
reinterpreted: they only carry `id`, so cross-checking one against the grid
needs the database:

```sh
sqlite3 /var/www/db/iptables.db \
  "SELECT rule_order, id, protocol, dport, target FROM filter ORDER BY rule_order;"
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
