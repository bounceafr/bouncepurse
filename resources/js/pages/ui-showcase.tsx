import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import {
  Check,
  Search,
  Settings,
  Bell,
  User,
  Plus,
  ChevronRight,
  AlertCircle,
  Info,
  CheckCircle2,
  MoreHorizontal,
  Home,
  Inbox,
  FileText,
} from 'lucide-react';
import { Button, buttonVariants } from '@/components/ui/button';
import { ButtonGroup, ButtonGroupSeparator } from '@/components/ui/button-group';
import { Input } from '@/components/ui/input';
import { InputGroup } from '@/components/ui/input-group';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Switch } from '@/components/ui/switch';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from '@/components/ui/card';
import {
  Alert,
  AlertDescription,
  AlertTitle,
} from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Empty,
  EmptyHeader,
  EmptyTitle,
  EmptyDescription,
  EmptyContent,
  EmptyMedia,
} from '@/components/ui/empty';
import { Progress } from '@/components/ui/progress';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';
import {
  HoverCard,
  HoverCardContent,
  HoverCardTrigger,
} from '@/components/ui/hover-card';
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import {
  Field,
  FieldGroup,
  FieldLabel,
  FieldDescription,
  FieldError,
  FieldContent,
} from '@/components/ui/field';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Slider } from '@/components/ui/slider';
import { Kbd } from '@/components/ui/kbd';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

const breadcrumbs: BreadcrumbItemType[] = [
  { title: 'UI Showcase' },
];

export default function UiShowcase() {
  const [progress, setProgress] = useState(0);
  const [checked, setChecked] = useState(false);
  const [radioValue, setRadioValue] = useState('option1');

  useEffect(() => {
    const timer = setTimeout(() => setProgress(66), 500);
    return () => clearTimeout(timer);
  }, []);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="UI Components Showcase" />

      <div className="@container/main flex flex-1 flex-col gap-2">
        <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
          <div className="px-4 lg:px-6">
            <h1 className="text-2xl font-semibold tracking-tight">
              UI Components Showcase
            </h1>
            <p className="mt-1 text-sm text-muted-foreground">
              Production-ready, accessible components built with shadcn/ui and Tailwind CSS.
            </p>
          </div>

          <div className="space-y-8 px-4 lg:px-6">
            {/* Buttons */}
            <Section title="Buttons" description="Interactive button components with various styles and sizes.">
              <div className="flex flex-wrap gap-3">
                <Button>Default</Button>
                <Button variant="secondary">Secondary</Button>
                <Button variant="outline">Outline</Button>
                <Button variant="ghost">Ghost</Button>
                <Button variant="destructive">Destructive</Button>
                <Button disabled>Disabled</Button>
              </div>
              <Separator className="my-4" />
              <div className="flex flex-wrap gap-3 items-center">
                <Button size="sm">Small</Button>
                <Button size="default">Default</Button>
                <Button size="lg">Large</Button>
                <Button size="icon">
                  <Settings />
                </Button>
              </div>
              <Separator className="my-4" />
              <div className="flex flex-wrap gap-3 items-center">
                <Button>
                  <Search data-icon="inline-start" />
                  Search
                </Button>
                <Button>
                  Share
                  <ChevronRight data-icon="inline-end" />
                </Button>
                <Button disabled>
                  <Spinner data-icon="inline-start" />
                  Loading
                </Button>
              </div>
              <Separator className="my-4" />
              <ButtonGroup>
                <Button variant="outline">One</Button>
                <ButtonGroupSeparator />
                <Button variant="outline">Two</Button>
                <ButtonGroupSeparator />
                <Button variant="outline">Three</Button>
              </ButtonGroup>
            </Section>

            {/* Inputs */}
            <Section title="Form Controls" description="Accessible form elements with proper labels and validation.">
              <FieldGroup className="max-w-md">
                <Field>
                  <FieldLabel htmlFor="name">Name</FieldLabel>
                  <FieldContent>
                    <Input id="name" placeholder="Enter your name" />
                    <FieldDescription>Your full real name.</FieldDescription>
                  </FieldContent>
                </Field>

                <Field data-invalid>
                  <FieldLabel htmlFor="email">Email</FieldLabel>
                  <FieldContent>
                    <Input id="email" type="email" placeholder="name@example.com" aria-invalid />
                    <FieldError>Please enter a valid email address.</FieldError>
                  </FieldContent>
                </Field>

                <Field>
                  <FieldLabel htmlFor="password">Password</FieldLabel>
                  <FieldContent>
                    <Input id="password" type="password" />
                    <FieldDescription>Must be at least 8 characters.</FieldDescription>
                  </FieldContent>
                </Field>

                <Field>
                  <FieldLabel htmlFor="bio">Bio</FieldLabel>
                  <FieldContent>
                    <Textarea id="bio" placeholder="Tell us about yourself" />
                  </FieldContent>
                </Field>

                <Field>
                  <FieldLabel>Search</FieldLabel>
                  <FieldContent>
                    <InputGroup>
                      <Input placeholder="Search..." />
                      <Button type="button" size="icon">
                        <Search />
                      </Button>
                    </InputGroup>
                  </FieldContent>
                </Field>
              </FieldGroup>

              <Separator className="my-6" />

              <FieldGroup className="max-w-md">
                <Field orientation="horizontal">
                  <FieldLabel htmlFor="notifications">Email notifications</FieldLabel>
                  <Switch id="notifications" checked={checked} onCheckedChange={setChecked} />
                </Field>

                <Field orientation="horizontal">
                  <FieldLabel>Terms of service</FieldLabel>
                  <div className="flex items-center gap-2">
                    <Checkbox id="terms" />
                    <Label htmlFor="terms" className="text-sm font-normal">I agree to the terms and conditions</Label>
                  </div>
                </Field>

                <Field>
                  <FieldLabel>Plan</FieldLabel>
                  <RadioGroup defaultValue="option1" value={radioValue} onValueChange={setRadioValue}>
                    <div className="flex items-center gap-2">
                      <RadioGroupItem value="option1" id="r1" />
                      <Label htmlFor="r1">Free</Label>
                    </div>
                    <div className="flex items-center gap-2">
                      <RadioGroupItem value="option2" id="r2" />
                      <Label htmlFor="r2">Pro</Label>
                    </div>
                    <div className="flex items-center gap-2">
                      <RadioGroupItem value="option3" id="r3" />
                      <Label htmlFor="r3">Enterprise</Label>
                    </div>
                  </RadioGroup>
                </Field>

                <Field>
                  <FieldLabel>Volume</FieldLabel>
                  <Slider defaultValue={[50]} max={100} step={1} />
                </Field>

                <Field>
                  <FieldLabel>Country</FieldLabel>
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder="Select a country" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>North America</SelectLabel>
                        <SelectItem value="us">United States</SelectItem>
                        <SelectItem value="ca">Canada</SelectItem>
                      </SelectGroup>
                      <SelectGroup>
                        <SelectLabel>Europe</SelectLabel>
                        <SelectItem value="uk">United Kingdom</SelectItem>
                        <SelectItem value="de">Germany</SelectItem>
                        <SelectItem value="fr">France</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                </Field>
              </FieldGroup>
            </Section>

            {/* Feedback */}
            <Section title="Feedback & Status" description="Components for communicating status and loading states.">
              <div className="space-y-4">
                <Alert>
                  <Info className="size-4" />
                  <AlertTitle>Heads up!</AlertTitle>
                  <AlertDescription>
                    You can add components to your app using the CLI.
                  </AlertDescription>
                </Alert>
                <Alert variant="destructive">
                  <AlertCircle className="size-4" />
                  <AlertTitle>Error</AlertTitle>
                  <AlertDescription>
                    Your session has expired. Please log in again.
                  </AlertDescription>
                </Alert>
                <Alert variant="success">
                  <CheckCircle2 className="size-4" />
                  <AlertTitle>Success</AlertTitle>
                  <AlertDescription>
                    Your changes have been saved successfully.
                  </AlertDescription>
                </Alert>
              </div>

              <Separator className="my-6" />

              <div className="space-y-4">
                <div className="flex items-center gap-4">
                  <span className="text-sm text-muted-foreground">Progress:</span>
                  <Progress value={progress} className="flex-1" />
                  <span className="text-sm font-medium">{progress}%</span>
                </div>

                <div className="space-y-2">
                  <div className="flex items-center gap-3">
                    <Spinner size="sm" />
                    <span className="text-sm text-muted-foreground">Loading small...</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <Spinner />
                    <span className="text-sm text-muted-foreground">Loading default...</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <Spinner size="lg" />
                    <span className="text-sm text-muted-foreground">Loading large...</span>
                  </div>
                </div>
              </div>
            </Section>

            {/* Loading & Empty States */}
            <Section title="Loading & Empty States" description="Skeletons for loading and empty states for when there's no data.">
              <div className="grid gap-6 md:grid-cols-2">
                <Card>
                  <CardHeader>
                    <CardTitle>Skeleton Loading</CardTitle>
                    <CardDescription>Simulates content loading</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="flex items-center gap-4">
                      <Skeleton className="size-12 rounded-full" />
                      <div className="space-y-2">
                        <Skeleton className="h-4 w-32" />
                        <Skeleton className="h-4 w-24" />
                      </div>
                    </div>
                    <Skeleton className="h-20 w-full" />
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle>Empty State</CardTitle>
                    <CardDescription>No data available</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <Empty>
                      <EmptyHeader>
                        <EmptyMedia variant="icon">
                          <Inbox />
                        </EmptyMedia>
                        <EmptyTitle>No messages</EmptyTitle>
                        <EmptyDescription>
                          You're all caught up! Check back later for new messages.
                        </EmptyDescription>
                      </EmptyHeader>
                      <EmptyContent>
                        <Button>
                          <Plus data-icon="inline-start" />
                          New Message
                        </Button>
                      </EmptyContent>
                    </Empty>
                  </CardContent>
                </Card>
              </div>
            </Section>

            {/* Data Display */}
            <Section title="Data Display" description="Components for displaying badges, avatars, and other data.">
              <div className="flex flex-wrap gap-3">
                <Badge>Default</Badge>
                <Badge variant="secondary">Secondary</Badge>
                <Badge variant="outline">Outline</Badge>
                <Badge variant="destructive">Destructive</Badge>
                <Badge variant="success">Success</Badge>
              </div>

              <Separator className="my-6" />

              <div className="flex items-center gap-4">
                <Avatar>
                  <AvatarImage src="https://github.com/shadcn.png" />
                  <AvatarFallback>CN</AvatarFallback>
                </Avatar>
                <Avatar>
                  <AvatarFallback>AB</AvatarFallback>
                </Avatar>
                <Avatar>
                  <AvatarFallback>CD</AvatarFallback>
                </Avatar>
              </div>

              <Separator className="my-6" />

              <TooltipProvider>
                <div className="flex items-center gap-3">
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Button variant="ghost" size="icon">
                        <Home />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>Home</TooltipContent>
                  </Tooltip>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Button variant="ghost" size="icon">
                        <Inbox />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>Inbox</TooltipContent>
                  </Tooltip>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Button variant="ghost" size="icon">
                        <FileText />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>Documents</TooltipContent>
                  </Tooltip>
                </div>
              </TooltipProvider>
            </Section>

            {/* Navigation */}
            <Section title="Navigation" description="Breadcrumbs, pagination, and tabs for navigation.">
              <Breadcrumb>
                <BreadcrumbList>
                  <BreadcrumbItem>
                    <BreadcrumbLink href="#">Home</BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem>
                    <BreadcrumbLink href="#">Components</BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem>
                    <BreadcrumbPage>Breadcrumb</BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>

              <Separator className="my-6" />

              <Pagination>
                <PaginationContent>
                  <PaginationItem>
                    <PaginationPrevious href="#" />
                  </PaginationItem>
                  <PaginationItem>
                    <PaginationLink href="#">1</PaginationLink>
                  </PaginationItem>
                  <PaginationItem>
                    <PaginationLink href="#" isActive>2</PaginationLink>
                  </PaginationItem>
                  <PaginationItem>
                    <PaginationLink href="#">3</PaginationLink>
                  </PaginationItem>
                  <PaginationItem>
                    <PaginationEllipsis />
                  </PaginationItem>
                  <PaginationItem>
                    <PaginationNext href="#" />
                  </PaginationItem>
                </PaginationContent>
              </Pagination>

              <Separator className="my-6" />

              <Tabs defaultValue="account" className="w-full max-w-md">
                <TabsList>
                  <TabsTrigger value="account">Account</TabsTrigger>
                  <TabsTrigger value="password">Password</TabsTrigger>
                  <TabsTrigger value="security">Security</TabsTrigger>
                </TabsList>
                <TabsContent value="account">
                  <Card>
                    <CardHeader>
                      <CardTitle>Account</CardTitle>
                      <CardDescription>
                        Make changes to your account here.
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                      <Field>
                        <FieldLabel htmlFor="username">Name</FieldLabel>
                        <FieldContent>
                          <Input id="username" defaultValue="John Doe" />
                        </FieldContent>
                      </Field>
                      <Field>
                        <FieldLabel htmlFor="user-email">Email</FieldLabel>
                        <FieldContent>
                          <Input id="user-email" defaultValue="john@example.com" />
                        </FieldContent>
                      </Field>
                    </CardContent>
                    <CardFooter>
                      <Button>Save changes</Button>
                    </CardFooter>
                  </Card>
                </TabsContent>
                <TabsContent value="password">
                  <Card>
                    <CardHeader>
                      <CardTitle>Password</CardTitle>
                      <CardDescription>
                        Change your password here.
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                      <Field>
                        <FieldLabel htmlFor="current-password">Current password</FieldLabel>
                        <FieldContent>
                          <Input id="current-password" type="password" />
                        </FieldContent>
                      </Field>
                      <Field>
                        <FieldLabel htmlFor="new-password">New password</FieldLabel>
                        <FieldContent>
                          <Input id="new-password" type="password" />
                        </FieldContent>
                      </Field>
                    </CardContent>
                    <CardFooter>
                      <Button>Save password</Button>
                    </CardFooter>
                  </Card>
                </TabsContent>
                <TabsContent value="security">
                  <Card>
                    <CardHeader>
                      <CardTitle>Security</CardTitle>
                      <CardDescription>
                        Manage your security settings.
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                      <Field orientation="horizontal">
                        <FieldLabel htmlFor="two-factor">Two-factor authentication</FieldLabel>
                        <Switch id="two-factor" />
                      </Field>
                    </CardContent>
                    <CardFooter>
                      <Button>Save settings</Button>
                    </CardFooter>
                  </Card>
                </TabsContent>
              </Tabs>
            </Section>

            {/* Overlays */}
            <Section title="Overlays & Modals" description="Dialogs, sheets, popovers, and dropdown menus.">
              <div className="flex flex-wrap gap-3">
                <Dialog>
                  <DialogTrigger asChild>
                    <Button>Open Dialog</Button>
                  </DialogTrigger>
                  <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                      <DialogTitle>Edit profile</DialogTitle>
                      <DialogDescription>
                        Make changes to your profile here. Click save when you're done.
                      </DialogDescription>
                    </DialogHeader>
                    <FieldGroup>
                      <Field>
                        <FieldLabel htmlFor="profile-name">Name</FieldLabel>
                        <FieldContent>
                          <Input id="profile-name" defaultValue="John Doe" />
                        </FieldContent>
                      </Field>
                      <Field>
                        <FieldLabel htmlFor="profile-username">Username</FieldLabel>
                        <FieldContent>
                          <Input id="profile-username" defaultValue="@johndoe" />
                        </FieldContent>
                      </Field>
                    </FieldGroup>
                    <DialogFooter>
                      <Button type="submit">Save changes</Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>

                <AlertDialog>
                  <AlertDialogTrigger asChild>
                    <Button variant="destructive">Delete Account</Button>
                  </AlertDialogTrigger>
                  <AlertDialogContent>
                    <AlertDialogHeader>
                      <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                      <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete your account
                        and remove your data from our servers.
                      </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                      <AlertDialogCancel>Cancel</AlertDialogCancel>
                      <AlertDialogAction>Continue</AlertDialogAction>
                    </AlertDialogFooter>
                  </AlertDialogContent>
                </AlertDialog>

                <Sheet>
                  <SheetTrigger asChild>
                    <Button variant="outline">Open Sheet</Button>
                  </SheetTrigger>
                  <SheetContent>
                    <SheetHeader>
                      <SheetTitle>Edit profile</SheetTitle>
                      <SheetDescription>
                        Make changes to your profile here. Click save when you're done.
                      </SheetDescription>
                    </SheetHeader>
                    <div className="py-6">
                      <FieldGroup>
                        <Field>
                          <FieldLabel htmlFor="sheet-name">Name</FieldLabel>
                          <FieldContent>
                            <Input id="sheet-name" defaultValue="John Doe" />
                          </FieldContent>
                        </Field>
                        <Field>
                          <FieldLabel htmlFor="sheet-username">Username</FieldLabel>
                          <FieldContent>
                            <Input id="sheet-username" defaultValue="@johndoe" />
                          </FieldContent>
                        </Field>
                      </FieldGroup>
                    </div>
                    <SheetFooter>
                      <Button type="submit">Save changes</Button>
                    </SheetFooter>
                  </SheetContent>
                </Sheet>

                <Popover>
                  <PopoverTrigger asChild>
                    <Button variant="ghost">Open Popover</Button>
                  </PopoverTrigger>
                  <PopoverContent className="w-80">
                    <div className="grid gap-4">
                      <div className="space-y-2">
                        <h4 className="font-medium">Dimensions</h4>
                        <p className="text-sm text-muted-foreground">
                          Set the dimensions for the layer.
                        </p>
                      </div>
                      <div className="grid gap-2">
                        <div className="grid grid-cols-3 items-center gap-4">
                          <Label htmlFor="width">Width</Label>
                          <Input id="width" defaultValue="100%" className="col-span-2 h-8" />
                        </div>
                        <div className="grid grid-cols-3 items-center gap-4">
                          <Label htmlFor="max-width">Max. width</Label>
                          <Input id="max-width" defaultValue="300px" className="col-span-2 h-8" />
                        </div>
                        <div className="grid grid-cols-3 items-center gap-4">
                          <Label htmlFor="height">Height</Label>
                          <Input id="height" defaultValue="25px" className="col-span-2 h-8" />
                        </div>
                        <div className="grid grid-cols-3 items-center gap-4">
                          <Label htmlFor="max-height">Max. height</Label>
                          <Input id="max-height" defaultValue="none" className="col-span-2 h-8" />
                        </div>
                      </div>
                    </div>
                  </PopoverContent>
                </Popover>

                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="outline">
                      <MoreHorizontal />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent className="w-56">
                    <DropdownMenuLabel>My Account</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem>Profile</DropdownMenuItem>
                    <DropdownMenuItem>Billing</DropdownMenuItem>
                    <DropdownMenuItem>Settings</DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem>Log out</DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>

              <Separator className="my-6" />

              <div className="flex flex-wrap gap-3 items-center">
                <HoverCard>
                  <HoverCardTrigger asChild>
                    <Button variant="link">@johndoe</Button>
                  </HoverCardTrigger>
                  <HoverCardContent className="w-80">
                    <div className="flex justify-between space-x-4">
                      <Avatar>
                        <AvatarImage src="https://github.com/shadcn.png" />
                        <AvatarFallback>JD</AvatarFallback>
                      </Avatar>
                      <div className="space-y-1">
                        <h4 className="text-sm font-semibold">John Doe</h4>
                        <p className="text-sm text-muted-foreground">
                          Full-stack developer working on cool things.
                        </p>
                        <div className="flex items-center pt-2">
                          <Badge variant="outline" className="rounded-xs">
                            <Check className="mr-1 size-3" />
                            Pro
                          </Badge>
                        </div>
                      </div>
                    </div>
                  </HoverCardContent>
                </HoverCard>

                <div className="text-sm text-muted-foreground">
                  Press <Kbd>⌘</Kbd> + <Kbd>K</Kbd> to open command palette
                </div>

                <Command>
                  <CommandInput placeholder="Type a command or search..." />
                  <CommandList>
                    <CommandEmpty>No results found.</CommandEmpty>
                    <CommandGroup heading="Suggestions">
                      <CommandItem>Calendar</CommandItem>
                      <CommandItem>Search Emoji</CommandItem>
                      <CommandItem>Calculator</CommandItem>
                    </CommandGroup>
                  </CommandList>
                </Command>
              </div>
            </Section>

            {/* Containers */}
            <Section title="Containers & Layout" description="Cards, accordions, and collapsible components.">
              <div className="grid gap-6 md:grid-cols-2">
                <Card>
                  <CardHeader>
                    <CardTitle>Create project</CardTitle>
                    <CardDescription>
                      Deploy your new project in one-click.
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <Field>
                      <FieldLabel htmlFor="project-name">Name</FieldLabel>
                      <FieldContent>
                        <Input id="project-name" placeholder="Name of your project" />
                      </FieldContent>
                    </Field>
                    <Field>
                      <FieldLabel htmlFor="project-framework">Framework</FieldLabel>
                      <FieldContent>
                        <Select>
                          <SelectTrigger id="project-framework">
                            <SelectValue placeholder="Select" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="next">Next.js</SelectItem>
                            <SelectItem value="sveltekit">SvelteKit</SelectItem>
                            <SelectItem value="astro">Astro</SelectItem>
                            <SelectItem value="nuxt">Nuxt.js</SelectItem>
                          </SelectContent>
                        </Select>
                      </FieldContent>
                    </Field>
                  </CardContent>
                  <CardFooter className="border-t pt-6">
                    <Button className="ml-auto">Deploy</Button>
                  </CardFooter>
                </Card>

                <Collapsible defaultOpen className="w-full border rounded-lg p-4">
                  <div className="flex items-center justify-between space-x-4">
                    <h4 className="text-sm font-semibold">
                      @peduarte starred 3 repositories
                    </h4>
                    <CollapsibleTrigger asChild>
                      <Button variant="ghost" size="sm">
                        <ChevronRight className="h-4 w-4 transition-transform" />
                        <span className="sr-only">Toggle</span>
                      </Button>
                    </CollapsibleTrigger>
                  </div>
                  <div className="text-sm text-muted-foreground">
                    @radix-ui/primitives
                  </div>
                  <CollapsibleContent className="mt-2 space-y-2 text-sm">
                    <div>@radix-ui/react-collapsible</div>
                    <div>@radix-ui/react-context-menu</div>
                  </CollapsibleContent>
                </Collapsible>
              </div>

              <Separator className="my-6" />

              <Accordion type="single" collapsible className="w-full max-w-md">
                <AccordionItem value="item-1">
                  <AccordionTrigger>Is it accessible?</AccordionTrigger>
                  <AccordionContent>
                    Yes. It adheres to the WAI-ARIA design pattern.
                  </AccordionContent>
                </AccordionItem>
                <AccordionItem value="item-2">
                  <AccordionTrigger>Is it styled?</AccordionTrigger>
                  <AccordionContent>
                    Yes. It comes with default styles that matches the other components' aesthetic.
                  </AccordionContent>
                </AccordionItem>
                <AccordionItem value="item-3">
                  <AccordionTrigger>Is it animated?</AccordionTrigger>
                  <AccordionContent>
                    Yes. It's animated by default, but you can disable it if you prefer.
                  </AccordionContent>
                </AccordionItem>
              </Accordion>
            </Section>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}

function Section({
  title,
  description,
  children,
}: {
  title: string;
  description?: string;
  children: React.ReactNode;
}) {
  return (
    <Card className="*:data-[slot=card]:shadow-none">
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {description && <CardDescription>{description}</CardDescription>}
      </CardHeader>
      <CardContent>{children}</CardContent>
    </Card>
  );
}
