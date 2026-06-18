import { useQuery } from '@tanstack/react-query'
import { fetchEmbedConfig, EmbedConfig } from '../api/api'

export function usePowerBIEmbed(postId: number) {
  return useQuery<EmbedConfig>({
    queryKey: ['powerbi-embed', postId],
    queryFn: () => fetchEmbedConfig(postId),
    // Embed tokens expire in ~60 min; refetch at 45 min to stay ahead of expiry.
    staleTime: 45 * 60 * 1000,
    retry: 1,
    enabled: postId > 0,
  })
}
