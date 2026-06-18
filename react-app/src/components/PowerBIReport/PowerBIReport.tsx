import { useRef, useMemo, useState, useEffect } from 'react';
import { PowerBIEmbed } from 'powerbi-client-react';
import { models, Report, Page } from 'powerbi-client';
import {
  BarLoader,
  BeatLoader,
  BounceLoader,
  CircleLoader,
  ClipLoader,
  ClockLoader,
  DotLoader,
  FadeLoader,
  GridLoader,
  HashLoader,
  MoonLoader,
  PacmanLoader,
  PropagateLoader,
  PuffLoader,
  PulseLoader,
  RingLoader,
  RiseLoader,
  RotateLoader,
  ScaleLoader,
  SkewLoader,
  SquareLoader,
  SyncLoader,
} from 'react-spinners';
import { usePowerBIEmbed } from '../../hooks/usePowerBIEmbed';
import { ReportContainer, SpinnerWrapper, StatusMessage } from './PowerBIReport.styles';

const SPINNERS = {
  bar:       BarLoader,
  beat:      BeatLoader,
  bounce:    BounceLoader,
  circle:    CircleLoader,
  clip:      ClipLoader,
  clock:     ClockLoader,
  dot:       DotLoader,
  fade:      FadeLoader,
  grid:      GridLoader,
  hash:      HashLoader,
  moon:      MoonLoader,
  pacman:    PacmanLoader,
  propagate: PropagateLoader,
  puff:      PuffLoader,
  pulse:     PulseLoader,
  ring:      RingLoader,
  rise:      RiseLoader,
  rotate:    RotateLoader,
  scale:     ScaleLoader,
  skew:      SkewLoader,
  square:    SquareLoader,
  sync:      SyncLoader,
} as const;

type SpinnerType = keyof typeof SPINNERS;

// Reused across renders — updateSettings() applies this whenever the report renders.
const FIT_TO_WIDTH = {
  layoutType: models.LayoutType.Custom,
  customLayout: { displayOption: models.DisplayOption.FitToWidth },
};

interface Props {
  postId: number;
  width: string;
  height: string;
}

export function PowerBIReport({ postId, width, height }: Props) {
  const { data, isLoading, isError } = usePowerBIEmbed(postId);
  const { powerbiDisplayStatus, spinnerType, spinnerColor } = window.ReportViewerPBI;
  const reportRef    = useRef<Report | null>(null);
  const containerRef = useRef<HTMLDivElement | null>(null);
  // Guard so we only fetch the page dimensions once.
  const ratioFetched = useRef(false);

  const [aspectRatio,    setAspectRatio]    = useState<number | null>(null);
  const [computedHeight, setComputedHeight] = useState<number | null>(null);

  const Spinner = SPINNERS[(spinnerType as SpinnerType)] ?? ClipLoader;

  // Once we have the aspect ratio, derive container height from its current width
  // and keep it in sync as the container resizes (responsive layout).
  useEffect(() => {
    if (!aspectRatio || !containerRef.current) return;

    const el     = containerRef.current;
    const update = (w: number) => setComputedHeight(Math.round(w * aspectRatio));

    update(el.getBoundingClientRect().width);

    const observer = new ResizeObserver((entries) => update(entries[0].contentRect.width));
    observer.observe(el);
    return () => observer.disconnect();
  }, [aspectRatio]);

  const eventHandlers = useMemo(
    () =>
      new Map<string, () => void>([
        [
          'rendered',
          () => {
            // Re-apply FitToWidth after every render so user scroll/pinch zoom is reset.
            reportRef.current?.updateSettings(FIT_TO_WIDTH);

            // Read the report's native canvas size once to compute the aspect ratio.
            if (reportRef.current && !ratioFetched.current) {
              ratioFetched.current = true;
              void reportRef.current.getPages().then((pages: Page[]) => {
                const active = pages.find((p) => p.isActive) ?? pages[0];
                // defaultSize is { width, height } in the report's design units.
                const size = (active as any)?.defaultSize as
                  | { width: number; height: number }
                  | undefined;
                if (size?.width && size?.height) {
                  setAspectRatio(size.height / size.width);
                }
              });
            }
          },
        ],
      ]),
    [],
  );

  return (
    <ReportContainer
      ref={containerRef}
      $width={width}
      style={{ height: computedHeight !== null ? `${computedHeight}px` : height }}
    >
      {isLoading && (
        <SpinnerWrapper>
          <Spinner color={spinnerColor} />
        </SpinnerWrapper>
      )}

      {(isError || (!isLoading && !data)) && powerbiDisplayStatus && (
        <StatusMessage>Unable to load report.</StatusMessage>
      )}

      {data && (
        <PowerBIEmbed
          embedConfig={{
            type: data.embedType,
            id: data.reportId,
            embedUrl: data.embedUrl,
            accessToken: data.accessToken,
            tokenType: models.TokenType.Aad,
            pageName: data.pageName,
            settings: {
              navContentPaneEnabled: false,
              filterPaneEnabled: false,
              ...FIT_TO_WIDTH,
            },
          }}
          cssClassName="powerbi-embed"
          eventHandlers={eventHandlers}
          getEmbeddedComponent={(embedded) => {
            reportRef.current = embedded as Report;
          }}
        />
      )}
    </ReportContainer>
  );
}
