import React from 'react'
import ReactDOM from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ThemeProvider } from 'styled-components'
import { PowerBIReport } from './components/PowerBIReport/index'
import { theme } from './styles/theme'

const queryClient = new QueryClient()

const rootEl = document.getElementById('powerbi-report-root')

if (rootEl) {
  const postId = Number(rootEl.dataset.postId)
  const width  = rootEl.dataset.width  ?? '100%'
  const height = rootEl.dataset.height ?? '600px'

  ReactDOM.createRoot(rootEl).render(
    <React.StrictMode>
      <QueryClientProvider client={queryClient}>
        <ThemeProvider theme={theme}>
          <PowerBIReport postId={postId} width={width} height={height} />
        </ThemeProvider>
      </QueryClientProvider>
    </React.StrictMode>
  )
}
