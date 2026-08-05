import "../css/app.css";

import { createFoundationApp, type FoundationAppConfig, type PageImport } from "@dashboard/app";

const config: FoundationAppConfig = {
  projectPages: import.meta.glob("@shop/Pages/**/*.tsx") as Record<string, PageImport>,
  projectPrefix: "shop",
};

createFoundationApp(config);
