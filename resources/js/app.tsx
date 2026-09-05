import "../css/app.css";

import { ShoppingCart } from "lucide-react";
import { createFoundationApp, type FoundationAppConfig, type PageImport } from "@dashboard/app";
import { registerCoreTemplates } from "@dashboard/dashboard";
import { registerFeatureIcons } from "@dashboard/lib/icons";

registerFeatureIcons({ ShoppingCart });

const config: FoundationAppConfig = {
  basePages: registerCoreTemplates(),
  projectPages: import.meta.glob("@shop/Pages/**/*.tsx") as Record<string, PageImport>,
  projectPrefix: "shop",
  pageOverrides: {
    "public/about": () => import("./public/about"),
    "public/services": () => import("./public/services"),
    "public/contact": () => import("./public/contact"),
  },
};

createFoundationApp(config);
