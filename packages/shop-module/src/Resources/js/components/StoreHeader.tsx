import { Link, usePage } from "@inertiajs/react";
import { useState } from "react";
import { Menu, X, ShoppingCart } from "lucide-react";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";
import { BrandLogo } from "@/Components/frontend/BrandLogo";
import { NavUser } from "@/Components/navigation/nav-user";
import { cn } from "@/lib/utils";

interface CartItem {
    quantity: number;
}

interface CartData {
    items: CartItem[];
}

export function StoreHeader() {
    const { props } = usePage();
    const brand = (props.brand as { logo?: string; favicon?: string; name?: string }) || {};
    const cart = (props.cart as CartData) || { items: [] };
    const itemCount = (cart.items || []).reduce((sum, item) => sum + item.quantity, 0);
    const appName = brand.name || "";

    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const isActive = (name: string) => route().current(name) || false;

    const linkClass = (active: boolean) =>
        cn(
            "px-3 py-2 text-sm font-medium rounded-md transition-colors",
            active ? "text-foreground bg-accent" : "text-muted-foreground hover:text-foreground hover:bg-accent"
        );

    const siteLinks = (
        <>
            <Link href={route("home")} className={linkClass(isActive("home"))}>
                Home
            </Link>
            <Link href={route("store.index")} className={linkClass(isActive("store.index"))}>
                Store
            </Link>
            <Link href={route("store.products")} className={linkClass(isActive("store.products"))}>
                Products
            </Link>
            <Link href={route("blog.index")} className={linkClass(isActive("blog.index"))}>
                Blog
            </Link>
            <Link href={route("about")} className={linkClass(isActive("about"))}>
                About
            </Link>
            <Link href={route("services")} className={linkClass(isActive("services"))}>
                Services
            </Link>
            <Link href={route("contact")} className={linkClass(isActive("contact"))}>
                Contact
            </Link>
        </>
    );

    return (
        <nav className="border-b border-border bg-background">
            <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <div className="flex items-center gap-6">
                    <Link href={route("home")} className="flex items-center gap-2 shrink-0">
                        <BrandLogo
                            logo={brand.logo || null}
                            favicon={brand.favicon || null}
                            name={appName}
                            size="sm"
                            showName
                        />
                    </Link>
                    <div className="hidden sm:flex items-center gap-1">{siteLinks}</div>
                </div>
                <div className="flex items-center gap-2">
                    <div className="hidden sm:flex">
                        <NavUser variant="basic" />
                    </div>
                    <Link
                        href={route("store.cart.index")}
                        className="relative flex items-center justify-center rounded-md p-2 text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                        aria-label={`Shopping cart, ${itemCount} item${itemCount !== 1 ? "s" : ""}`}
                    >
                        <ShoppingCart className="h-5 w-5" />
                        {itemCount > 0 && (
                            <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground">
                                {itemCount}
                            </span>
                        )}
                    </Link>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="sm:hidden"
                        aria-label={mobileMenuOpen ? "Close menu" : "Open menu"}
                        aria-expanded={mobileMenuOpen}
                        aria-controls="store-mobile-menu"
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                    >
                        {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                    </Button>
                </div>
            </div>
            {mobileMenuOpen && (
                <div id="store-mobile-menu" className="border-t border-border sm:hidden">
                    <div className="space-y-1 px-4 py-3">
                        <Link
                            href={route("home")}
                            className="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-accent"
                            onClick={() => setMobileMenuOpen(false)}
                        >
                            Home
                        </Link>
                        <Link
                            href={route("store.index")}
                            className="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-accent"
                            onClick={() => setMobileMenuOpen(false)}
                        >
                            Store
                        </Link>
                        <Link
                            href={route("store.products")}
                            className="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-accent"
                            onClick={() => setMobileMenuOpen(false)}
                        >
                            Products
                        </Link>
                        <Link
                            href={route("blog.index")}
                            className="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-accent"
                            onClick={() => setMobileMenuOpen(false)}
                        >
                            Blog
                        </Link>
                        <Link
                            href={route("about")}
                            className="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-accent"
                            onClick={() => setMobileMenuOpen(false)}
                        >
                            About
                        </Link>
                        <Link
                            href={route("services")}
                            className="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-accent"
                            onClick={() => setMobileMenuOpen(false)}
                        >
                            Services
                        </Link>
                        <Link
                            href={route("contact")}
                            className="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-accent"
                            onClick={() => setMobileMenuOpen(false)}
                        >
                            Contact
                        </Link>
                        <Separator className="my-2" />
                        <div className="flex items-center justify-between">
                            <NavUser variant="basic" />
                            <Link
                                href={route("store.cart.index")}
                                className="relative flex items-center justify-center rounded-md p-2 text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                                aria-label={`Shopping cart, ${itemCount} item${itemCount !== 1 ? "s" : ""}`}
                            >
                                <ShoppingCart className="h-5 w-5" />
                                {itemCount > 0 && (
                                    <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground">
                                        {itemCount}
                                    </span>
                                )}
                            </Link>
                        </div>
                    </div>
                </div>
            )}
        </nav>
    );
}

export default StoreHeader;
