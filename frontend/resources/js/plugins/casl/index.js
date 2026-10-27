import { createMongoAbility } from '@casl/ability'
import { abilitiesPlugin } from '@casl/vue'

export default function (app) {
  const userAbilityRules = useCookie('userAbilityRules')

  // Default: "manage all" so nav items are visible when no stored rules.
  // Real authorization is enforced by the backend (Spatie roles + gates).
  const rules = userAbilityRules.value ?? [{ action: 'manage', subject: 'all' }]
  const initialAbility = createMongoAbility(rules)

  app.use(abilitiesPlugin, initialAbility, {
    useGlobalProperties: true,
  })
}
