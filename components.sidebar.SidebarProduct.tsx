import { useRouter } from 'next/router'
import { useEffect } from 'react'
import cx from 'classnames'
 
import { Link } from 'components/Link'
import { useMainContext } from 'components/context/MainContext'
import { AllProductsLink } from 'components/sidebar/AllProductsLink'
import styles from './SidebarProduct.module.scss'
import { RestCollapsibleSection } from './RestCollapsibleSection'
import { ProductCollapsibleSection } from './ProductCollapsibleSection'
import { useTranslation } from 'components/hooks/useTranslation'
 
export const SidebarProduct = () => {
  const router = useRouter()
  const { currentProduct, currentProductTree } = useMainContext()
  const { t } = useTranslation(['products'])
 
  useEffect(() => {
    const activeArticle = document.querySelector('[data-is-current-page=true]')
    // Setting to the top doesn't give enough context of surrounding categories
    activeArticle?.scrollIntoView({ block: 'center' })
    // scrollIntoView affects some articles that are very low in the sidebar
    // The content scrolls down a bit. This sets the article content back up
    // top unless the route contains a link heading.
    if (!router.asPath.includes('#')) window?.scrollTo(0, 0)
  }, [])
 
  if (!currentProductTree) {
    return null
  }
 
  // remove query string and hash
  const routePath = `/${router.locale}${router.asPath.split('?')[0].split('#')[0]}`
 
  const hasExactCategory = !!currentProductTree?.childPages.find(({ href }) =>
    routePath.includes(href)
  )
 
  const productTitle = currentProductTree.renderedShortTitle || currentProductTree.renderedFullTitle
 
  const productSection = () => (
    <li className="my-3" data-testid="product-sidebar-items">
      <ul className="list-style-none">
        {currentProductTree &&
          currentProductTree.childPages.map((childPage, i) => {
            const isStandaloneCategory = childPage.page.documentType === 'article'
 
            const childTitle = childPage.renderedShortTitle || childPage.renderedFullTitle
            const isActive =
              routePath.includes(childPage.href + '/') || routePath === childPage.href
            const defaultOpen = hasExactCategory ? isActive : false
            return (
              <li
                key={childPage.href + i}
                data-is-active-category={isActive}
                data-is-current-page={isActive && isStandaloneCategory}
                className={cx('py-1', isActive && 'color-bg-inset')}
              >
                {isStandaloneCategory ? (
                  <Link
                    href={childPage.href}
                    className="pl-4 pr-2 py-2 d-block flex-auto mr-3 color-fg-default no-underline text-bold"
                  >
                    {childTitle}
                  </Link>
                ) : (
                  <ProductCollapsibleSection
                    defaultOpen={defaultOpen}
                    routePath={routePath}
                    title={childTitle}
                    page={childPage}
                  />
                )}
              </li>
            )
          })}
      </ul>
    </li>
  )
 
  const restSection = () => {
    const conceptualPages = currentProductTree.childPages.filter(
      (page) => page.href.includes('guides') || page.href.includes('overview')
    )
    const restPages = currentProductTree.childPages.filter(
      (page) => !page.href.includes('guides') && !page.href.includes('overview')
    )
    return (
      <>
        <li className="my-3">
          <ul className="list-style-none">
            {conceptualPages.map((childPage, i) => {
              const isStandaloneCategory = childPage.page.documentType === 'article'
              const childTitle = childPage.renderedShortTitle || childPage.renderedFullTitle
              const isActive =
                routePath.includes(childPage.href + '/') || routePath === childPage.href
              const defaultOpen = hasExactCategory ? isActive : false