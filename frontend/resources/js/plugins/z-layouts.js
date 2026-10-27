import { createLayouts } from '@layouts'
import { layoutConfig } from '@themeConfig'

// Styles
import '@layouts/styles/index.scss'

export default function (app) {
  app.use(createLayouts(layoutConfig))
}
