import "../css/app.css";

import { createFoundationApp } from "@nreljed/platform/app";
import { registerShopFeatureIcons } from "@nreljed/shop-module/icons";
import { initShopI18n } from "@nreljed/shop-module/lib/i18n";
import { pageConfig } from "./page-config";

registerShopFeatureIcons();
initShopI18n();

createFoundationApp(pageConfig);
