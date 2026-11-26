interface ProseContentProps {
  html: string;
}

// Local replacement for the platform's removed `Components/blog/ProseContent`.
// The stored HTML is author-controlled (same contract as the old component);
// styling lives in `resources/css/site.css` under `.site-prose`.
export default function ProseContent({ html }: ProseContentProps) {
  return <div className="site-prose" dangerouslySetInnerHTML={{ __html: html }} />;
}
