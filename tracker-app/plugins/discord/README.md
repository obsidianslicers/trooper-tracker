# Discord plugin

Announces each newly posted event in a Discord channel, mentioning the Discord role of the
organization hosting it. Enable it if your club coordinates in Discord and wants troopers there
to hear about new events without checking the tracker.

The plugin is off until an administrator enables it (**Command Staff > Plugins**, or
`php artisan plugins:enable discord`). See [docs/PLUGINS.md](../../../docs/PLUGINS.md) for how
plugins are managed.

## Configuration

| Setting | Type | Fallback | Purpose |
|---------|------|----------|---------|
| **Webhook URL** (`webhook_url`) | secret | `DISCORD_WEBHOOK_URL` | The Discord channel webhook events are posted to. |

You need a webhook for the target channel (in Discord: channel settings > Integrations >
Webhooks). Without a webhook URL the plugin does nothing.

Each organization can also have a **Discord Mention**, set on its edit page (for example
`<@&123456789>` for a role). It is put at the start of that organization's announcements.

## What it adds

- **Event announcements.** A listener on `EventPosted` posts a message to the webhook:
  `<mention> <event name> has been posted.`, plus the event's troop credit notice when there is
  one, and an embed with the event name, comments and a link to the event page. The bot name is
  `<APP_NAME> Bot`. A failed post is logged as a warning and never stops the rest of the
  event-posted work.
- **Organization form field.** A **Discord Mention** field (max 128 characters) on the
  organization edit form (`admin.organizations.update.fields` slot). Saving it empty removes the
  mention.
- **System Check row.** "Discord webhook" passes when a webhook URL is set.
- **Table.** `tt_discord_organization_mentions` (one mention per organization). Its migration
  copies any values left in the old core column `tt_organizations.discord_mention`.
- **Seeder.** `FloridaGarrisonMentionSeeder` stores the Florida Garrison squads' role mentions:

  ```bash
  php artisan db:seed --class="TroopTracker\Plugins\Discord\Database\Seeders\FloridaGarrisonMentionSeeder"
  ```

No routes, pages, scheduled tasks or console commands.

## Limitations

- One webhook for the whole site: every organization's events go to the same channel.
- Only new events are announced. Updates, cancellations and closings are not posted.
- The announcement text ("has been posted.") is English and is not translated.

## Tests

From `tracker-app`:

```bash
php artisan test plugins/discord/tests
```
