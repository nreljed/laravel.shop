import { Link } from "@inertiajs/react";
import { ArrowRight, Award, HeartHandshake, Lightbulb, Rocket, ShieldCheck, Target } from "lucide-react";
import PublicLayout from "@/Layouts/PublicLayout";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Separator } from "@/Components/ui/separator";

const values = [
    {
        icon: Rocket,
        title: "Innovation",
        description: "We embrace modern tooling and pragmatic engineering to ship fast and stay ahead.",
    },
    {
        icon: ShieldCheck,
        title: "Trust & Reliability",
        description: "Secure, well-tested, and dependable — our platform is built to be relied upon.",
    },
    {
        icon: HeartHandshake,
        title: "Customer First",
        description: "Every decision starts with what serves you. Support that actually supports.",
    },
    {
        icon: Target,
        title: "Quality Obsessed",
        description: "Attention to detail at every layer, from the storefront to the checkout.",
    },
] as const;

const milestones = [
    { year: "Started", title: "The beginning", description: "We set out to build a modern commerce platform from first principles." },
    { year: "Today", title: "In production", description: "Powering real storefronts with a complete product, order, and inventory workflow." },
] as const;

export default function About() {
    return (
        <PublicLayout
            title="About Us"
            description="Learn about our mission, values, and the team building a modern commerce platform."
            ogType="website"
        >
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                <div className="mx-auto max-w-3xl text-center">
                    <div className="inline-flex items-center gap-2 rounded-full border bg-muted/40 px-3 py-1 text-sm text-muted-foreground">
                        <Award className="h-4 w-4" />
                        <span>Our story</span>
                    </div>
                    <h1 className="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">
                        About our platform
                    </h1>
                    <p className="mt-6 text-lg leading-relaxed text-muted-foreground">
                        We build commerce software that feels effortless — a complete dashboard,
                        storefront, and inventory system designed to help you sell more with less
                        friction. This is a project template, so put your company's real story here.
                    </p>
                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        <Button asChild size="lg">
                            <Link href={route("services")}>
                                Explore services <ArrowRight className="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                        <Button asChild size="lg" variant="outline">
                            <Link href={route("contact")}>Get in touch</Link>
                        </Button>
                    </div>
                </div>

                <Separator className="my-16" />

                <div className="grid gap-10 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <h2 className="text-3xl font-bold tracking-tight">Our mission</h2>
                        <p className="mt-4 text-lg leading-relaxed text-muted-foreground">
                            Our mission is to make powerful commerce tooling accessible to every
                            seller. We combine a rich admin experience with a fast, modern storefront
                            so you can focus on your products instead of your platform.
                        </p>
                        <div className="mt-8 space-y-4">
                            {milestones.map((m) => (
                                <div key={m.title} className="flex gap-4 rounded-lg border bg-card p-4">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                        <Lightbulb className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{m.year}</p>
                                        <h3 className="font-semibold">{m.title}</h3>
                                        <p className="mt-1 text-sm text-muted-foreground">{m.description}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                    <div>
                        <h2 className="text-3xl font-bold tracking-tight">What we value</h2>
                        <p className="mt-4 text-lg text-muted-foreground">
                            These principles guide every feature we ship.
                        </p>
                        <div className="mt-8 grid gap-4 sm:grid-cols-2">
                            {values.map((v) => (
                                <Card key={v.title}>
                                    <CardContent className="p-5">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-md bg-primary/10 text-primary">
                                            <v.icon className="h-5 w-5" />
                                        </div>
                                        <h3 className="mt-4 font-semibold">{v.title}</h3>
                                        <p className="mt-2 text-sm text-muted-foreground">{v.description}</p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
