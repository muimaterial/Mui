// src/router/routeConfig.jsx
import { LayoutA, LayoutB, LayoutC } from "../layouts";
import { 
  Comp1, Comp2, Comp3, Comp4, Comp5, Comp6, Comp7,
  Comp8, Comp9, Comp10, Comp11, Comp12,
  Comp13, Comp14, Comp15, Comp16, Comp17, Comp18 
} from "../components";

export const routeConfig = [
  {
    layout: LayoutA,
    routes: [
      { path: "/a1", element: <Comp1 /> },
      { path: "/a2", element: <Comp2 /> },
      { path: "/a3", element: <Comp3 /> },
      { path: "/a4", element: <Comp4 /> },
      { path: "/a5", element: <Comp5 /> },
      { path: "/a6", element: <Comp6 /> },
      { path: "/a7", element: <Comp7 /> },
    ],
  },
  {
    layout: LayoutB,
    routes: [
      { path: "/b1", element: <Comp8 /> },
      { path: "/b2", element: <Comp9 /> },
      { path: "/b3", element: <Comp10 /> },
      { path: "/b4", element: <Comp11 /> },
      { path: "/b5", element: <Comp12 /> },
    ],
  },
  {
    layout: LayoutC,
    routes: [
      { path: "/c1", element: <Comp13 /> },
      { path: "/c2", element: <Comp14 /> },
      { path: "/c3", element: <Comp15 /> },
      { path: "/c4", element: <Comp16 /> },
      { path: "/c5", element: <Comp17 /> },
      { path: "/c6", element: <Comp18 /> },
    ],
  },
];



// src/router/index.jsx
import { createBrowserRouter, RouterProvider } from "react-router-dom";
import { routeConfig } from "./routeConfig";

const router = createBrowserRouter(
  routeConfig.map(({ layout: LayoutComponent, routes }) => ({
    element: <LayoutComponent />,
    children: routes,
  }))
);

export function AppRouter() {
  return <RouterProvider router={router} />;
}


const flatRoutes = [
  { path: "/a1", element: <Comp1 />, layout: "A" },
  { path: "/b1", element: <Comp8 />, layout: "B" },
  // ...
];

const layoutMap = {
  A: LayoutA,
  B: LayoutB,
  C: LayoutC,
};

// Group flat routes by layout
const groupedRoutes = flatRoutes.reduce((acc, route) => {
  const { layout, ...routeProps } = route;
  if (!acc[layout]) acc[layout] = [];
  acc[layout].push(routeProps);
  return acc;
}, {});

// Transform into React Router format
const router = createBrowserRouter(
  Object.entries(groupedRoutes).map(([layoutKey, routes]) => ({
    element: <layoutMap[layoutKey] />,
    children: routes,
  }))
);
    
