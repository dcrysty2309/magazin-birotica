#!/usr/bin/env python3
"""
Generate or send individualized outreach emails to suppliers.

Usage:
  python3 tools/outreach_suppliers.py --csv tools/suppliers.example.csv --dry-run
  python3 tools/outreach_suppliers.py --csv tools/suppliers.csv --send

CSV columns:
  name,email,website,notes

Environment variables for SMTP:
  SMTP_HOST
  SMTP_PORT            (default: 587)
  SMTP_USER
  SMTP_PASS
  SMTP_FROM
  SMTP_NAME            (default: SupplyHub)
  SMTP_STARTTLS        (default: 1)
  SMTP_SSL             (default: 0)
"""

from __future__ import annotations

import argparse
import csv
import os
import smtplib
import sys
from dataclasses import dataclass
from email.message import EmailMessage
from pathlib import Path
from typing import Iterable


DEFAULT_SUBJECT = "Parteneriat B2B / dropshipping pentru papetărie"
DEFAULT_BODY = """Bună ziua {name},

Mă numesc {sender_name} și dezvolt un magazin online de papetărie și birotică.
Caut un parteneriat B2B sau dropshipping pentru produsele din portofoliul vostru.

Mă interesează în special:
- acces la catalog / platformă B2B;
- condiții comerciale și discounturi;
- timp de livrare și cost transport;
- dacă aveți integrare, feed sau listă de produse;
- dacă puteți livra direct către clientul final.

Dacă este posibil, vă rog să-mi trimiteți:
- condițiile de colaborare;
- lista de categorii relevante;
- un cont B2B sau instrucțiuni de acces, dacă există.

Mulțumesc,
{sender_name}
{sender_email}
"""


@dataclass
class Supplier:
    name: str
    email: str
    website: str = ""
    notes: str = ""


def read_suppliers(csv_path: Path) -> list[Supplier]:
    suppliers: list[Supplier] = []
    with csv_path.open(newline="", encoding="utf-8-sig") as fh:
        reader = csv.DictReader(fh)
        required = {"name", "email"}
        missing = required - set(reader.fieldnames or [])
        if missing:
            raise SystemExit(f"CSV missing columns: {', '.join(sorted(missing))}")
        for row in reader:
            name = (row.get("name") or "").strip()
            email = (row.get("email") or "").strip()
            if not name or not email:
                continue
            suppliers.append(
                Supplier(
                    name=name,
                    email=email,
                    website=(row.get("website") or "").strip(),
                    notes=(row.get("notes") or "").strip(),
                )
            )
    return suppliers


def render_body(template: str, supplier: Supplier, sender_name: str, sender_email: str) -> str:
    body = template.format(
        name=supplier.name,
        email=supplier.email,
        website=supplier.website,
        notes=supplier.notes,
        sender_name=sender_name,
        sender_email=sender_email,
    )
    return body.strip() + "\n"


def build_message(subject: str, body: str, sender_name: str, sender_email: str, recipient: Supplier) -> EmailMessage:
    msg = EmailMessage()
    msg["Subject"] = subject
    msg["From"] = f"{sender_name} <{sender_email}>"
    msg["To"] = f"{recipient.name} <{recipient.email}>"
    msg["Reply-To"] = sender_email
    msg.set_content(body)
    return msg


def write_draft(out_dir: Path, recipient: Supplier, message: EmailMessage) -> Path:
    out_dir.mkdir(parents=True, exist_ok=True)
    safe_name = "".join(ch for ch in recipient.name if ch.isalnum() or ch in ("-", "_")).strip("_-") or "supplier"
    draft_path = out_dir / f"{safe_name}.eml"
    draft_path.write_text(message.as_string(), encoding="utf-8")
    return draft_path


def env_bool(name: str, default: bool = False) -> bool:
    raw = os.getenv(name)
    if raw is None:
        return default
    return raw.strip().lower() in {"1", "true", "yes", "on"}


def send_messages(messages: Iterable[tuple[Supplier, EmailMessage]]) -> None:
    host = os.getenv("SMTP_HOST", "").strip()
    port = int(os.getenv("SMTP_PORT", "587"))
    user = os.getenv("SMTP_USER", "").strip()
    password = os.getenv("SMTP_PASS", "").strip()
    sender_email = os.getenv("SMTP_FROM", "").strip()

    if not host or not user or not password or not sender_email:
        raise SystemExit("SMTP_HOST, SMTP_USER, SMTP_PASS and SMTP_FROM are required for --send.")

    use_ssl = env_bool("SMTP_SSL", False)
    use_starttls = env_bool("SMTP_STARTTLS", True)

    client: smtplib.SMTP | smtplib.SMTP_SSL
    if use_ssl:
        client = smtplib.SMTP_SSL(host, port, timeout=30)
    else:
        client = smtplib.SMTP(host, port, timeout=30)

    with client as smtp:
        smtp.ehlo()
        if use_starttls and not use_ssl:
            smtp.starttls()
            smtp.ehlo()
        smtp.login(user, password)
        for supplier, message in messages:
            smtp.send_message(message, from_addr=sender_email, to_addrs=[supplier.email])
            print(f"Sent to {supplier.name} <{supplier.email}>")


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Generate or send outreach emails to suppliers.")
    parser.add_argument("--csv", required=True, help="CSV with columns: name,email,website,notes")
    parser.add_argument("--subject", default=DEFAULT_SUBJECT, help="Email subject")
    parser.add_argument("--sender-name", default=os.getenv("SENDER_NAME", "SupplyHub"))
    parser.add_argument("--sender-email", default=os.getenv("SENDER_EMAIL", ""))
    parser.add_argument("--template-file", help="Optional plain-text template file")
    parser.add_argument("--out-dir", default="tools/outreach-drafts", help="Where to write .eml drafts")
    parser.add_argument("--send", action="store_true", help="Actually send emails over SMTP")
    parser.add_argument("--dry-run", action="store_true", help="Do not send, only print/write drafts")
    parser.add_argument("--limit", type=int, default=0, help="Process only the first N suppliers")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    csv_path = Path(args.csv)
    if not csv_path.exists():
        raise SystemExit(f"CSV not found: {csv_path}")

    sender_email = args.sender_email.strip()
    if not sender_email:
        raise SystemExit("Set SENDER_EMAIL or pass --sender-email.")

    template = DEFAULT_BODY
    if args.template_file:
        template_path = Path(args.template_file)
        template = template_path.read_text(encoding="utf-8")

    suppliers = read_suppliers(csv_path)
    if args.limit and args.limit > 0:
        suppliers = suppliers[: args.limit]

    if not suppliers:
        print("No suppliers found in CSV.", file=sys.stderr)
        return 1

    messages: list[tuple[Supplier, EmailMessage]] = []
    for supplier in suppliers:
        body = render_body(template, supplier, args.sender_name, sender_email)
        message = build_message(args.subject, body, args.sender_name, sender_email, supplier)
        messages.append((supplier, message))

    if args.send:
        send_messages(messages)
        return 0

    out_dir = Path(args.out_dir)
    for supplier, message in messages:
        draft_path = write_draft(out_dir, supplier, message)
        print(f"Wrote draft: {draft_path}")
        print(f"To: {supplier.email}")
        print(f"Subject: {args.subject}")
        print("-" * 40)

    if args.dry_run or not args.send:
        print(f"Dry run complete. Drafts saved to {out_dir}.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
