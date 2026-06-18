import axios from 'axios'

const client = axios.create({
  baseURL: window.ReportViewerPBI.restUrl,
  headers: { 'X-WP-Nonce': window.ReportViewerPBI.nonce },
  withCredentials: true,
})

export interface EmbedConfig {
  embedUrl: string
  accessToken: string
  reportId: string
  embedType: 'report' | 'dashboard'
  pageName?: string
}

export async function fetchEmbedConfig(postId: number): Promise<EmbedConfig> {
  const res = await client.get('report-viewer-for-pbi/v1/powerbi/embed', {
    params: { post_id: postId },
  })
  return res.data
}
