import { Link } from "@inertiajs/react";
import { ArrowRight, Boxes, CheckCircle2, CreditCard, Mail, Package, ShoppingCart, Truck } from "lucide-react";
import PublicLayout from "@/Layouts/PublicLayout";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";

const services = [
    {
        icon: ShoppingCart,
        title: "Modern storefront",
        description: "A fast, responsive storefront with products, categories, cart, and a smooth guest checkout.",
        items: ["Product grid & detail pages", "Cart with coupons & auto-discounts", "Guest checkout with order confirmation"],
    },
    {
        icon: Boxes,
        title: "Merchant dashboard",
        description: "Everything you need to run the shop from a single control panel.",
        items: ["Products, categories, tags & brands", "Orders with status workflow & refunds", "Customers, reviews & coupons"],
    },
    {
        icon: Package,
        title: "Inventory management",
        description: "Keep stock accurate with a full audit trail and low-stock alerts.",
        items: ["Sales, returns & manual adjustments", "Hold stock with automatic release", "Variations with attribute swatches"],
    },
    {
        icon: Truck,
        title: "Shipping & fulfilment",
        description: "Zones, methods, and per-method pricing that match how you actually ship.",
        items: ["Country-based shipping zones", "Flat-rate & free-above methods", "Per-zone override pricing"],
    },
    {
        icon: CreditCard,
        title: "Pricing & discounts",
        description: "Flexible pricing with sales scheduling and a rich discount engine.",
        items: ["Automatic & code-based coupons", "Currency & tax configuration", "Sale prices with date windows"],
    },
    {
        icon: Mail,
        title: "Engagement & reporting",
        description: "Keep customers informed and understand how the business is performing.",
        items: ["Transactional email templates", "Revenue & product reports", "Notification preferences"],
    },
] as const;

export default function Services() {
    return (
        <PublicLayout
            title="Services"
            description="Explore everything our commerce platform offers — from storefront and dashboard to inventory, shipping, and reporting."
            ogType="website"
        >
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                <div className="mx-auto max-w-3xl text-center">
                    <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">Everything you need to sell</h1>
                    <p className="mt-6 text-lg leading-relaxed text-muted-foreground">
                        A complete commerce toolkit in one platform. This is a project template —
                        replace these with your own services and feature highlights.
                    </p>
                </div>

                <div className="mt-16 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {services.map((s) => (
                        <Card key={s.title} className="flex flex-col">
                            <CardContent className="flex flex-1 flex-col p-6">
                                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <s.icon className="h-6 w-6" />
                                </div>
                                <h2 className="mt-5 text-xl font-semibold">{s.title}</h2>
                                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">{s.description}</p>
                                <ul className="mt-5 space-y-2.5">
                                    {s.items.map((item) => (
                                        <li key={item} className="flex items-start gap-2 text-sm">
                                            <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="mt-16 flex flex-col items-center gap-4 rounded-xl border bg-muted/30 p-10 text-center">
                    <h2 className="text-2xl font-bold tracking-tight">Curious about a specific feature?</h2>
                    <p className="max-w-xl text-muted-foreground">
                        Tell us what you're trying to achieve and we'll point you in the right direction.
                    </p>
                    <Button asChild size="lg">
                        <Link href={route("contact")}>
                            Contact us <ArrowRight className="ml-2 h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </section>
        </PublicLayout>
    );
}
