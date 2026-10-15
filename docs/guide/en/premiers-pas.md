# Getting started

Your site has just been installed ([Installation](installation.md)): here, in order, is what turns it from an
empty page into a site you can open to your community. Allow an hour. Each step points to the guide that covers
it in detail.

## 1. Enter the administration

Log in with the account created at the end of the installer, then open `/en/admin` (or the *Administration*
link in the member bar). The sidebar sorts modules into sections — *Content*, *Community*, *System*… —;
`Ctrl + K` opens the quick search. The full tour is in [The administration panel](admin.md).

## 2. Name your site

*System → Settings*: the site name, its description, the contact address, the time zone. The name shows in the
theme's header and in the browser tab; the description is used by search engines as long as a page does not
give one of its own ([Search engines](admin.md#search-engines)).

## 3. Choose your theme and colours

*System → Themes & Addons*: each theme shows its home page as a thumbnail. Enable the one that looks like your
community, then open its settings: the **colours** page shows the change live, and refuses a colour that would
make text unreadable. What a theme is, its zones and its regions: [Concepts](concepts.md).

## 4. Build the home page

*System → Live Editor*: the site is shown as visitors see it, with its regions outlined. Drop **widgets** into
them — the latest news, the forum, members online, a slideshow — and set each one in a click. A layout applies
to one page or to the whole site.

## 5. Build the menu

*Content → Menus*: add the links of the navigation bar — home, the forum, the news, a page. A link to a module is
picked from a list; an external link is pasted.

## 6. Publish a first news item

*Content → News → New news item*: a title, an image, the text. It appears on the home page if you placed the news
widget there, and readers can comment on it if comments are open.

## 7. Open the forum

*Community → Forum*: create a **category** (“General”), then its **forums** (“Introductions”, “Help”).
**Prefixes** (“Question”, “Solved”) help sort topics. Who reads and who writes is set per category, with the
padlock button.

## 8. Welcome members

*System → Settings → Registrations*: open, by invitation or closed, with or without a captcha
([Registrations](admin.md#registrations)). **Roles** decide what each person can do: create a “Moderator” role,
tick its rights in the matrix, and give it to trusted members ([Permissions](admin.md#permissions)).

## 9. Add what is missing

The site was installed with a profile — gaming, community, association. A missing module (the calendar,
donations, collectives, the Discord bot…) is added from *System → Themes & Addons* or the
[marketplace](marketplace.md), without reinstalling.

## 10. Keep it safe

*System → Monitoring*: create a first **backup**, and note the address of the **scheduled task**. Hand it to your
host (a cron task, every hour): it is what publishes scheduled content, sends reminders and collects what
external services do not announce ([Monitoring](admin.md#monitoring)).

Does your site speak several languages? Read [A site in several languages](multilingue.md) next.
