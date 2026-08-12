/* Testing Library no desmonta lo que pintó a menos que alguien se lo pida, y sin esto el
   segundo test de un fichero encuentra dos botones «ENTRAR»: el suyo y el del anterior. */

import { cleanup } from "@testing-library/react";
import { afterEach } from "vitest";

afterEach(cleanup);
