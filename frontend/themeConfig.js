import { breakpointsVuetifyV3 } from '@vueuse/core'
import { VIcon } from 'vuetify/components/VIcon'
import { defineThemeConfig } from '@core'
import { Skins } from '@core/enums'
import { AppContentLayoutNav, ContentWidth, FooterType, NavbarType } from '@layouts/enums'

export const { themeConfig, layoutConfig } = defineThemeConfig({
  app: {
    title: 'GMO Travel',
    logo: h('img', { src: '/img/logo-eco.png', alt: 'Ecogreen', style: 'height:28px;width:auto;' }),
    contentWidth: ContentWidth.Fluid,
    contentLayoutNav: AppContentLayoutNav.Vertical,
    overlayNavFromBreakpoint: breakpointsVuetifyV3.lg - 1,
    i18n: {
      enable: false,
      defaultLocale: 'id',
      langConfig: [
        {
          label: 'Indonesia',
          i18nLang: 'id',
          isRTL: false,
        },
      ],
    },
    theme: 'system',
    skin: Skins.Default,
    iconRenderer: VIcon,
  },
  navbar: {
    type: NavbarType.Sticky,
    navbarBlur: true,
  },
  footer: { type: FooterType.Static },
  verticalNav: {
    isVerticalNavCollapsed: false,
    defaultNavItemIconProps: { icon: 'ri-circle-line' },
    isVerticalNavSemiDark: false,
  },
  horizontalNav: {
    type: 'sticky',
    transition: 'slide-y-reverse-transition',
    popoverOffset: 4,
  },
  icons: {
    chevronDown: { icon: 'bx-chevron-down' },
    chevronRight: { icon: 'bx-chevron-right' },
    close: { icon: 'bx-x', size: '20' },
    verticalNavPinned: { icon: 'bx-radio-circle-marked', size: '20' },
    verticalNavUnPinned: { icon: 'bx-circle', size: '20' },
    sectionTitlePlaceholder: { icon: 'bx-minus' },
  },
})
