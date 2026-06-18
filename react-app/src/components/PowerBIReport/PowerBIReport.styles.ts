import styled from 'styled-components'

export const ReportContainer = styled.div<{ $width: string }>`
  width: ${({ $width }) => $width};

  /*
   * PowerBIEmbed renders an intermediate div (.powerbi-embed) then the iframe
   * inside it. Both must fill the container explicitly — they won't inherit
   * dimensions automatically.
   */
  .powerbi-embed {
    width: 100%;
    height: 100%;

    iframe {
      width: 100%;
      height: 100%;
      border: none;
      display: block;
    }
  }
`

export const SpinnerWrapper = styled.div`
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
`

export const StatusMessage = styled.p`
  margin: 0;
  padding: ${({ theme }) => theme.space.md};
  color: ${({ theme }) => theme.color.textPrimary};
  font-family: ${({ theme }) => theme.font.family};
`
